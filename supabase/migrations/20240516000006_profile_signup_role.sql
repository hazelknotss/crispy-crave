-- Allow rider self-registration via auth.signUp({ options: { data: { signup_role: 'rider' } } }).
-- Only 'rider' is accepted from client metadata; anything else defaults to 'user'.
-- Never promote to admin/restaurant from metadata.

create or replace function public.handle_new_user ()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  meta_role text;
  new_role text;
begin
  meta_role := lower(trim(coalesce(new.raw_user_meta_data ->> 'signup_role', '')));
  if meta_role = 'rider' then
    new_role := 'rider';
  else
    new_role := 'user';
  end if;

  insert into public.profiles (id, display_name, role, approval_status)
  values (
    new.id,
    coalesce(new.raw_user_meta_data ->> 'display_name', new.email),
    new_role,
    'pending'
  );
  return new;
end;
$$;
