-- Optional menu category (Crispy Picks / POS). Safe to re-run.
alter table public.menus add column if not exists category varchar(80);
