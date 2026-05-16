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

- Import this repo and set **Root Directory** to **`web`** (required — the Next app is not at the repo root).
- **Framework preset:** Next.js. Leave **Output Directory** empty (default). Do not set it to `.next` manually.
- Add `NEXT_PUBLIC_SUPABASE_URL` and `NEXT_PUBLIC_SUPABASE_ANON_KEY` in the Vercel project environment.
- In Supabase → Auth → URL configuration, set **Site URL** and **Redirect URLs** to your production (and preview) URLs.
- Open the site from **Deployments → Production → Visit** (not an old preview URL from a failed PR).

### If you see `404 NOT_FOUND` (Vercel error page)

1. **Settings → General → Root Directory** must be exactly `web`.
2. Confirm the latest deployment status is **Ready**, not **Error**.
3. Use the production URL from the dashboard (e.g. `https://crispy-crave-….vercel.app`).
4. Try incognito or clear site data (PWA service worker can cache a bad response).
5. Optional: set env `DISABLE_PWA=1` and redeploy.

## Repo layout

- `web/` — Next.js app (deploy this to Vercel)
- `supabase/migrations/` — SQL migrations
- `supabase/email-templates/` — Auth email HTML (paste into Supabase Dashboard)
- Legacy PHP at repo root / `admin/`, `rider/`, etc.

## License

No license file is included by default; add one (e.g. MIT) if you want to specify terms for reuse.
