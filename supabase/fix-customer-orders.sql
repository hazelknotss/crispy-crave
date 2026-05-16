-- Run in Supabase SQL Editor so customers can see My orders / track / cancel.
-- Same as migration 20240516000011_customer_orders.sql

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
      select 1 from public.orders o
      where o.id = order_items.order_id and o.customer_id = auth.uid()
    )
  );

drop policy if exists "order_messages_customer_select" on public.order_messages;
drop policy if exists "order_messages_customer_insert" on public.order_messages;
drop policy if exists "order_messages_rider_select" on public.order_messages;
drop policy if exists "order_messages_rider_insert" on public.order_messages;

create policy "order_messages_customer_select" on public.order_messages for select to authenticated
  using (exists (select 1 from public.orders o where o.id = order_messages.order_id and o.customer_id = auth.uid()));

create policy "order_messages_customer_insert" on public.order_messages for insert to authenticated
  with check (
    sender_user_id = auth.uid() and sender_role = 'user'
    and exists (select 1 from public.orders o where o.id = order_messages.order_id and o.customer_id = auth.uid() and o.rider_id is not null)
  );

create policy "order_messages_rider_select" on public.order_messages for select to authenticated
  using (exists (select 1 from public.orders o where o.id = order_messages.order_id and o.rider_id = auth.uid()));

create policy "order_messages_rider_insert" on public.order_messages for insert to authenticated
  with check (
    sender_user_id = auth.uid() and sender_role = 'rider'
    and exists (select 1 from public.orders o where o.id = order_messages.order_id and o.rider_id = auth.uid())
  );

grant select, update on public.orders to authenticated;
grant select on public.order_items to authenticated;
grant select, insert, update on public.order_messages to authenticated;
