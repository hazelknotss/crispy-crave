-- Rider fleet profile + onboarding documents (mirrors PHP `riders` + `rider_documents`).
-- Run after profiles + signup_role migration.

create table if not exists public.rider_profiles (
  user_id uuid primary key references public.profiles (id) on delete cascade,
  phone text,
  vehicle_type text not null default 'motorcycle'
    check (vehicle_type in ('motorcycle', 'bicycle', 'car')),
  vehicle_plate text,
  fleet_status text not null default 'available'
    check (fleet_status in ('available', 'busy', 'offline')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create index if not exists rider_profiles_user_id_idx on public.rider_profiles (user_id);

create table if not exists public.rider_documents (
  id bigint generated always as identity primary key,
  user_id uuid not null references public.profiles (id) on delete cascade,
  doc_type text not null
    check (doc_type in ('license', 'registration', 'id_photo', 'other')),
  storage_path text not null,
  status text not null default 'pending'
    check (status in ('pending', 'approved', 'rejected')),
  uploaded_at timestamptz not null default now()
);

create index if not exists rider_documents_user_id_idx on public.rider_documents (user_id);

alter table public.rider_profiles enable row level security;
alter table public.rider_documents enable row level security;

drop policy if exists "rider_profiles_select_own" on public.rider_profiles;
drop policy if exists "rider_profiles_insert_own" on public.rider_profiles;
drop policy if exists "rider_profiles_update_own" on public.rider_profiles;

create policy "rider_profiles_select_own"
  on public.rider_profiles
  for select
  to authenticated
  using (auth.uid() = user_id);

create policy "rider_profiles_insert_own"
  on public.rider_profiles
  for insert
  to authenticated
  with check (auth.uid() = user_id);

create policy "rider_profiles_update_own"
  on public.rider_profiles
  for update
  to authenticated
  using (auth.uid() = user_id)
  with check (auth.uid() = user_id);

drop policy if exists "rider_documents_select_own" on public.rider_documents;
drop policy if exists "rider_documents_insert_own" on public.rider_documents;

create policy "rider_documents_select_own"
  on public.rider_documents
  for select
  to authenticated
  using (auth.uid() = user_id);

create policy "rider_documents_insert_own"
  on public.rider_documents
  for insert
  to authenticated
  with check (auth.uid() = user_id);

grant select, insert, update on public.rider_profiles to authenticated;
grant select, insert on public.rider_documents to authenticated;

-- Private bucket for license / ID uploads
insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values (
  'rider-documents',
  'rider-documents',
  false,
  5242880,
  array['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf']::text[]
)
on conflict (id) do nothing;

drop policy if exists "rider_documents_storage_insert" on storage.objects;
drop policy if exists "rider_documents_storage_select" on storage.objects;

create policy "rider_documents_storage_insert"
  on storage.objects
  for insert
  to authenticated
  with check (
    bucket_id = 'rider-documents'
    and split_part(name, '/', 1) = auth.uid()::text
  );

create policy "rider_documents_storage_select"
  on storage.objects
  for select
  to authenticated
  using (
    bucket_id = 'rider-documents'
    and split_part(name, '/', 1) = auth.uid()::text
  );
