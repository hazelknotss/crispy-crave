-- Use auth_profile_* helpers on orders (avoids extra profiles reads under RLS).

drop policy if exists "staff_orders_select" on public.orders;
drop policy if exists "staff_orders_update" on public.orders;

create policy "staff_orders_select"
  on public.orders
  for select
  to authenticated
  using (
    public.auth_profile_role () = 'admin'
    or (
      public.auth_profile_role () = 'restaurant'
      and public.auth_profile_restaurant_id () = orders.shop_id
    )
  );

create policy "staff_orders_update"
  on public.orders
  for update
  to authenticated
  using (
    public.auth_profile_role () = 'admin'
    or (
      public.auth_profile_role () = 'restaurant'
      and public.auth_profile_restaurant_id () = orders.shop_id
    )
  )
  with check (
    public.auth_profile_role () = 'admin'
    or (
      public.auth_profile_role () = 'restaurant'
      and public.auth_profile_restaurant_id () = orders.shop_id
    )
  );

drop policy if exists "staff_order_items_select" on public.order_items;

create policy "staff_order_items_select"
  on public.order_items
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.orders o
      where o.id = order_items.order_id
        and (
          public.auth_profile_role () = 'admin'
          or (
            public.auth_profile_role () = 'restaurant'
            and public.auth_profile_restaurant_id () = o.shop_id
          )
        )
    )
  );
