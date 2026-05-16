-- Run once in Supabase → SQL Editor (fixes staff/customer profile reads).
-- Same as migration 20240516000009_fix_profiles_rls_recursion.sql

create or replace function public.auth_profile_role ()
returns text
language sql
security definer
set search_path = public
stable
as $$
  select role from public.profiles where id = auth.uid();
$$;

create or replace function public.auth_profile_restaurant_id ()
returns bigint
language sql
security definer
set search_path = public
stable
as $$
  select restaurant_id from public.profiles where id = auth.uid();
$$;

revoke all on function public.auth_profile_role () from public;
revoke all on function public.auth_profile_restaurant_id () from public;
grant execute on function public.auth_profile_role () to authenticated;
grant execute on function public.auth_profile_restaurant_id () to authenticated;

drop policy if exists "staff_profiles_list_riders" on public.profiles;

create policy "staff_profiles_list_riders"
  on public.profiles
  for select
  to authenticated
  using (
    profiles.role = 'rider'
    and profiles.approval_status = 'approved'
    and (
      public.auth_profile_role () = 'admin'
      or (
        public.auth_profile_role () = 'restaurant'
        and public.auth_profile_restaurant_id () is not null
        and profiles.restaurant_id = public.auth_profile_restaurant_id ()
      )
    )
  );
