import { NextResponse, type NextRequest } from "next/server";

/**
 * Vercel deploys middleware on the Edge runtime. `@supabase/ssr` currently trips
 * "unsupported modules" there. Session refresh still runs wherever we call
 * `createClient()` in Server Components / route handlers (layouts, pages).
 *
 * When you upgrade to Next.js 15.5+ you can switch back to Node middleware +
 * `updateSession` from `./lib/supabase/middleware` (see Supabase SSR guide).
 */
export function middleware(_request: NextRequest) {
  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except static files, manifest, and PWA precache scripts.
     */
    "/((?!_next/static|_next/image|favicon.ico|manifest.webmanifest|sw\\.js|workbox|swe-worker|feedback\\.js|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
