import type { SupabaseClient } from "@supabase/supabase-js";
import { createAdminClient } from "@/lib/supabase/admin";

/** Find auth user id by email (admin API). Returns null if not found. */
export async function findAuthUserIdByEmail(
  email: string,
  admin: SupabaseClient | null = createAdminClient()
): Promise<string | null> {
  if (!admin) return null;

  const needle = email.trim().toLowerCase();
  let page = 1;

  while (true) {
    const { data, error } = await admin.auth.admin.listUsers({ page, perPage: 200 });
    if (error) {
      throw error;
    }
    const hit = data.users.find((u) => u.email?.toLowerCase() === needle);
    if (hit?.id) return hit.id;
    if (data.users.length < 200) return null;
    page += 1;
  }
}
