# Crispy Crave

Multi-portal food ordering: **customer storefront**, **rider** area, and **staff** (platform admin + kitchen) portal. The live web app is **Next.js 15** in `web/` with **Supabase** (Auth + Postgres). Legacy **PHP** and SQL live alongside for reference.

## URLs (same deployment)

| Portal   | Path |
|----------|------|
| Customers | `/` |
| Riders | `/rider`, `/rider/login` |
| Staff | `/admin`, `/admin/login` |

## Quick start (local)

1. `cd web`
2. Copy `web/.env.local.example` to `web/.env.local` and set Supabase keys (see file for names).
3. `npm install` then `npm run dev`

## Deploy (Vercel)

- Import this repo and set **Root Directory** to `web`.
- Add `NEXT_PUBLIC_SUPABASE_URL` and `NEXT_PUBLIC_SUPABASE_ANON_KEY` in the Vercel project environment.
- In Supabase → Auth → URL configuration, set **Site URL** and **Redirect URLs** to your production (and preview) URLs.

## Repo layout

- `web/` — Next.js app (deploy this to Vercel)
- `supabase/migrations/` — SQL migrations
- `supabase/email-templates/` — Auth email HTML (paste into Supabase Dashboard)
- Legacy PHP at repo root / `admin/`, `rider/`, etc.

## License

No license file is included by default; add one (e.g. MIT) if you want to specify terms for reuse.
