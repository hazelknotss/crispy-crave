import { createClient as createSupabaseClient } from "@supabase/supabase-js";
import { createAdminClient } from "@/lib/supabase/admin";
import { getSupabasePublicConfig } from "@/lib/supabase/env";

export type ProfileStaffRow = {
  display_name: string | null;
  role: string;
  restaurant_id: number | null;
};

async function queryProfile(
  userId: string,
  accessToken?: string | null
): Promise<{ profile: ProfileStaffRow | null; error: string | null }> {
  const admin = createAdminClient();
  const select = "display_name, role, restaurant_id";

  if (admin) {
    const { data, error } = await admin
      .from("profiles")
      .select(select)
      .eq("id", userId)
      .maybeSingle();
    if (error) return { profile: null, error: error.message };
    return { profile: (data as ProfileStaffRow | null) ?? null, error: null };
  }

  const token = accessToken?.trim();
  const cfg = getSupabasePublicConfig();
  if (token && cfg) {
    const client = createSupabaseClient(cfg.url, cfg.anonKey, {
      global: { headers: { Authorization: `Bearer ${token}` } },
      auth: { persistSession: false, autoRefreshToken: false },
    });
    const { data, error } = await client
      .from("profiles")
      .select(select)
      .eq("id", userId)
      .maybeSingle();
    if (error) return { profile: null, error: error.message };
    return { profile: (data as ProfileStaffRow | null) ?? null, error: null };
  }

  return { profile: null, error: null };
}

/** Read profile.role for a user id (server-only). */
export async function fetchProfileRole(
  userId: string,
  accessToken?: string | null
): Promise<{ role: string | null; error: string | null }> {
  const { profile, error } = await queryProfile(userId, accessToken);
  if (error) return { role: null, error };
  return { role: profile?.role ?? null, error: null };
}

/** Full staff profile row (server-only). */
export async function fetchStaffProfile(
  userId: string,
  accessToken?: string | null
): Promise<{ profile: ProfileStaffRow | null; error: string | null }> {
  return queryProfile(userId, accessToken);
}
