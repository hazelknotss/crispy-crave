-- Use this if fix-customer-orders.sql failed on ALTER TABLE.
-- Prerequisites (add in Table Editor if missing):
--   orders: cancel_reason (text), cancelled_at (timestamptz)
--   order_messages table — run full fix-customer-orders.sql as postgres, or create table from migration file
--
-- SQL Editor → Role: **postgres** → Run this file

select current_user;

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
