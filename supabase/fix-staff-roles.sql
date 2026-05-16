-- Run in Supabase → SQL Editor if staff login fails on role.
-- If you see "infinite recursion" on profiles, run fix-profiles-rls-recursion.sql first.
-- Promotes demo accounts by email (same as scripts/seed-portal-users.ps1).

update public.profiles p
set
  role = 'admin',
  approval_status = 'approved',
  display_name = coalesce(p.display_name, 'Platform Admin'),
  updated_at = now()
from auth.users u
where p.id = u.id
  and lower(u.email) = lower('admin@crispy.com');

update public.profiles p
set
  role = 'rider',
  approval_status = 'approved',
  display_name = coalesce(p.display_name, 'Demo Rider'),
  updated_at = now()
from auth.users u
where p.id = u.id
  and lower(u.email) = lower('rider@crispy.com');

-- Check result (email must match the account you use at /admin/login):
select u.id, u.email, p.display_name, p.role, p.approval_status
from auth.users u
join public.profiles p on p.id = u.id
where lower(u.email) in ('admin@crispy.com', 'rider@crispy.com');

-- If Platform Admin row has role admin but login still fails, compare ids:
-- Authentication → Users → open your email → UID must equal profiles.id for that row.
