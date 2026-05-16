-- Orders + line items for Next.js rider portal (mirrors PHP `orders` / `order_items` intent).
-- Run after core catalog + profiles.

create table if not exists public.orders (
  id bigint generated always as identity primary key,
  customer_id uuid references auth.users (id) on delete set null,
  customer_display_name text not null default 'Customer',
  shop_id bigint not null references public.restaurants (id),
  total numeric(10, 2) not null,
  payment_method text not null default 'cod',
  payment_status text not null default 'pending',
  order_status text not null default 'pending',
  delivery_status text not null default 'assigned',
  delivery_address text not null default '',
  barangay text not null default '',
  distance_km numeric(10, 2) not null default 0,
  rider_fee numeric(10, 2) not null default 0,
  rider_id uuid references auth.users (id) on delete set null,
  pickup_time time,
  delivery_proof_url text,
  delivery_proof_note text,
  delivery_proof_at timestamptz,
  created_at timestamptz not null default now(),
  constraint orders_delivery_status_chk check (
    delivery_status in ('assigned', 'picked_up', 'on_the_way', 'delivered')
  ),
  constraint orders_payment_method_chk check (payment_method in ('cod', 'gcash'))
);

create index if not exists orders_rider_id_idx on public.orders (rider_id);
create index if not exists orders_shop_id_idx on public.orders (shop_id);
create index if not exists orders_created_at_idx on public.orders (created_at desc);

create table if not exists public.order_items (
  id bigint generated always as identity primary key,
  order_id bigint not null references public.orders (id) on delete cascade,
  menu_id bigint not null references public.menus (id),
  menu_name text not null default '',
  price numeric(10, 2) not null,
  quantity int not null default 1
);

create index if not exists order_items_order_id_idx on public.order_items (order_id);

alter table public.orders enable row level security;
alter table public.order_items enable row level security;

-- Rider visibility: assigned to me OR unassigned pool (same rules as PHP kk_fetch_rider_orders).
create or replace function public.order_visible_to_rider (order_row public.orders)
returns boolean
language sql
stable
security invoker
set search_path = public
as $$
  select
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and pr.role = 'rider'
        and pr.approval_status = 'approved'
    )
    and (
      order_row.rider_id = auth.uid()
      or (
        order_row.rider_id is null
        and order_row.barangay not ilike '%pickup%'
        and order_row.delivery_address not ilike '%pickup%'
        and (
          (select pr.restaurant_id from public.profiles pr where pr.id = auth.uid()) is null
          or order_row.shop_id = (select pr.restaurant_id from public.profiles pr where pr.id = auth.uid())
        )
      )
    );
$$;

drop policy if exists "rider_orders_select" on public.orders;
drop policy if exists "rider_orders_update" on public.orders;
drop policy if exists "rider_order_items_select" on public.order_items;

create policy "rider_orders_select"
  on public.orders
  for select
  to authenticated
  using (public.order_visible_to_rider (orders));

create policy "rider_orders_update"
  on public.orders
  for update
  to authenticated
  using (public.order_visible_to_rider (orders))
  with check (public.order_visible_to_rider (orders));

create policy "rider_order_items_select"
  on public.order_items
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.orders o
      where o.id = order_items.order_id
        and public.order_visible_to_rider (o)
    )
  );

grant select, update on public.orders to authenticated;
grant select on public.order_items to authenticated;
