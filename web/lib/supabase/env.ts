/** Normalize project URL from env (fixes common copy-paste mistakes). */
export function normalizeSupabaseUrl(raw: string): string {
  let url = raw.trim();
  if (url.includes("/rest/v1")) {
    url = url.split("/rest/v1")[0]!;
  }
  if (url.includes("/auth/v1")) {
    url = url.split("/auth/v1")[0]!;
  }
  return url.replace(/\/+$/, "");
}

export function getSupabasePublicConfig(): { url: string; anonKey: string } | null {
  const rawUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const anonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY?.trim();
  if (!rawUrl?.length || !anonKey?.length) {
    return null;
  }
  return { url: normalizeSupabaseUrl(rawUrl), anonKey };
}
