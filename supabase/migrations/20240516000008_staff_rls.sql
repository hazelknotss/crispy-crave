-- Staff (platform admin + restaurant managers): read/write scoped by role + restaurant_id.
-- Complements public read policies on restaurants/menus (OR-combined with these).

-- —— Restaurants: staff can read shops they manage (admin: all rows) ——
drop policy if exists "staff_restaurants_select" on public.restaurants;

create policy "staff_restaurants_select"
  on public.restaurants
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = restaurants.id)
        )
    )
  );

-- —— Menus: staff full manage for own shops ——
drop policy if exists "staff_menus_select" on public.menus;
drop policy if exists "staff_menus_insert" on public.menus;
drop policy if exists "staff_menus_update" on public.menus;
drop policy if exists "staff_menus_delete" on public.menus;

create policy "staff_menus_select"
  on public.menus
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = menus.restaurant_id)
        )
    )
  );

create policy "staff_menus_insert"
  on public.menus
  for insert
  to authenticated
  with check (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = menus.restaurant_id)
        )
    )
  );

create policy "staff_menus_update"
  on public.menus
  for update
  to authenticated
  using (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = menus.restaurant_id)
        )
    )
  )
  with check (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = menus.restaurant_id)
        )
    )
  );

create policy "staff_menus_delete"
  on public.menus
  for delete
  to authenticated
  using (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = menus.restaurant_id)
        )
    )
  );

grant insert, update, delete on public.menus to authenticated;

-- —— Orders: staff read + update for their shop (or all shops if admin) ——
drop policy if exists "staff_orders_select" on public.orders;
drop policy if exists "staff_orders_update" on public.orders;

create policy "staff_orders_select"
  on public.orders
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = orders.shop_id)
        )
    )
  );

create policy "staff_orders_update"
  on public.orders
  for update
  to authenticated
  using (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = orders.shop_id)
        )
    )
  )
  with check (
    exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = orders.shop_id)
        )
    )
  );

grant select on public.orders to authenticated;

-- —— Order line items visible with parent order ——
drop policy if exists "staff_order_items_select" on public.order_items;

create policy "staff_order_items_select"
  on public.order_items
  for select
  to authenticated
  using (
    exists (
      select 1
      from public.orders o
      join public.profiles pr on pr.id = auth.uid()
      where o.id = order_items.order_id
        and (
          pr.role = 'admin'
          or (pr.role = 'restaurant' and pr.restaurant_id = o.shop_id)
        )
    )
  );

grant select on public.order_items to authenticated;

-- —— Staff can read approved rider profiles (assign dropdown) scoped by restaurant ——
drop policy if exists "staff_profiles_list_riders" on public.profiles;

create policy "staff_profiles_list_riders"
  on public.profiles
  for select
  to authenticated
  using (
    profiles.role = 'rider'
    and profiles.approval_status = 'approved'
    and exists (
      select 1
      from public.profiles pr
      where pr.id = auth.uid()
        and (
          pr.role = 'admin'
          or (
            pr.role = 'restaurant'
            and pr.restaurant_id is not null
            and profiles.restaurant_id = pr.restaurant_id
          )
        )
    )
  );
