import { createServerClient } from "@supabase/ssr";
import { NextResponse, type NextRequest } from "next/server";

function normalizeSupabaseUrl(raw: string): string {
  let url = raw.trim();
  if (url.includes("/rest/v1")) url = url.split("/rest/v1")[0]!;
  if (url.includes("/auth/v1")) url = url.split("/auth/v1")[0]!;
  return url.replace(/\/+$/, "");
}

/** Refresh Supabase auth cookies on every navigation (required for staff portal RSC). */
export async function middleware(request: NextRequest) {
  const { pathname, searchParams } = request.nextUrl;

  if (pathname === "/order-track.php") {
    const id = searchParams.get("id");
    if (id) {
      return NextResponse.redirect(new URL(`/order-track/${id}`, request.url));
    }
  }
  if (pathname === "/order-chat.php") {
    const id = searchParams.get("id");
    if (id) {
      return NextResponse.redirect(new URL(`/order-chat/${id}`, request.url));
    }
  }

  let response = NextResponse.next({ request });

  const rawUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const key = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY?.trim();
  if (!rawUrl?.length || !key) {
    return response;
  }

  const supabase = createServerClient(normalizeSupabaseUrl(rawUrl), key, {
    cookies: {
      getAll() {
        return request.cookies.getAll();
      },
      setAll(cookiesToSet: { name: string; value: string; options?: object }[]) {
        cookiesToSet.forEach(({ name, value }) => request.cookies.set(name, value));
        response = NextResponse.next({ request });
        cookiesToSet.forEach(({ name, value, options }) =>
          response.cookies.set(name, value, options)
        );
      },
    },
  });

  await supabase.auth.getUser();

  return response;
}

export const config = {
  matcher: [
    "/((?!_next/static|_next/image|favicon.ico|manifest.webmanifest|sw\\.js|workbox|swe-worker|feedback\\.js|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
