-- =============================================================================
-- CUSTOMER ORDERS FIX — read before running
-- =============================================================================
-- Error "must be owner of table orders" means you are NOT running as postgres.
--
-- In Supabase Dashboard:
--   1. Open SQL Editor (left sidebar) — NOT Table Editor → RLS tab
--   2. New query → paste this whole file
--   3. Bottom-right: Role must be **postgres** (not anon / authenticated / service_role)
--   4. Run
--
-- If ALTER still fails, add columns manually first (Table Editor → orders):
--   - cancel_reason  (text, nullable)
--   - cancelled_at (timestamptz, nullable)
-- Then run only from "-- POLICIES" downward, or use fix-customer-orders-policies-only.sql
-- =============================================================================

-- Optional: confirm you are postgres (should return "postgres")
select current_user;

alter table public.orders
  add column if not exists cancel_reason text,
  add column if not exists cancelled_at timestamptz;

create table if not exists public.order_messages (
  id bigint generated always as identity primary key,
  order_id bigint not null references public.orders (id) on delete cascade,
  sender_user_id uuid not null references auth.users (id) on delete cascade,
  sender_role text not null check (sender_role in ('user', 'rider')),
  body text not null,
  read_at_customer timestamptz,
  read_at_rider timestamptz,
  created_at timestamptz not null default now()
);

create index if not exists order_messages_order_id_idx on public.order_messages (order_id);

alter table public.order_messages enable row level security;

-- POLICIES (customer can read/cancel own orders)
drop policy if exists "customer_orders_select_own" on public.orders;
drop policy if exists "customer_orders_update_cancel" on public.orders;

create policy "customer_orders_select_own"
  on public.orders
  for select
  to authenticated
  using (customer_id = auth.uid());

create policy "customer_orders_update_cancel"
  on public.orders
  for update
  to authenticated
  using (customer_id = auth.uid())
  with check (customer_id = auth.uid());

drop policy if exists "customer_order_items_select" on public.order_items;

create policy "customer_order_items_select"
  on public.order_items
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.orders o
      where o.id = order_items.order_id
        and o.customer_id = auth.uid()
    )
  );

drop policy if exists "order_messages_customer_select" on public.order_messages;
drop policy if exists "order_messages_customer_insert" on public.order_messages;
drop policy if exists "order_messages_rider_select" on public.order_messages;
drop policy if exists "order_messages_rider_insert" on public.order_messages;

create policy "order_messages_customer_select"
  on public.order_messages
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.orders o
      where o.id = order_messages.order_id
        and o.customer_id = auth.uid()
    )
  );

create policy "order_messages_customer_insert"
  on public.order_messages
  for insert
  to authenticated
  with check (
    sender_user_id = auth.uid()
    and sender_role = 'user'
    and exists (
      select 1
      from public.orders o
      where o.id = order_messages.order_id
        and o.customer_id = auth.uid()
        and o.rider_id is not null
    )
  );

create policy "order_messages_rider_select"
  on public.order_messages
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.orders o
      where o.id = order_messages.order_id
        and o.rider_id = auth.uid()
    )
  );

create policy "order_messages_rider_insert"
  on public.order_messages
  for insert
  to authenticated
  with check (
    sender_user_id = auth.uid()
    and sender_role = 'rider'
    and exists (
      select 1
      from public.orders o
      where o.id = order_messages.order_id
        and o.rider_id = auth.uid()
    )
  );

-- Grants omitted (already set by migrations). Do not run GRANT unless you are postgres owner.
