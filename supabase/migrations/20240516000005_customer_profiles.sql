-- Customer profile extras (mirrors MySQL `customer_profiles` for Next.js profile page).

create table if not exists public.customer_profiles (
  user_id uuid primary key references public.profiles (id) on delete cascade,
  phone text,
  gcash_number text,
  gcash_account_name text,
  bank_name text,
  bank_account_name text,
  bank_account_number text,
  card_holder_name text,
  card_last4 text,
  card_brand text,
  card_exp_month smallint,
  card_exp_year smallint,
  preferred_payment text,
  updated_at timestamptz not null default now(),
  constraint customer_profiles_preferred_payment_chk check (
    preferred_payment is null
    or preferred_payment in ('cod', 'gcash', 'bank', 'card')
  )
);

alter table public.customer_profiles enable row level security;

drop policy if exists "customer_profiles_select_own" on public.customer_profiles;
drop policy if exists "customer_profiles_insert_own" on public.customer_profiles;
drop policy if exists "customer_profiles_update_own" on public.customer_profiles;

create policy "customer_profiles_select_own"
  on public.customer_profiles
  for select
  to authenticated
  using (auth.uid() = user_id);

create policy "customer_profiles_insert_own"
  on public.customer_profiles
  for insert
  to authenticated
  with check (auth.uid() = user_id);

create policy "customer_profiles_update_own"
  on public.customer_profiles
  for update
  to authenticated
  using (auth.uid() = user_id)
  with check (auth.uid() = user_id);

grant select, insert, update on public.customer_profiles to authenticated;
