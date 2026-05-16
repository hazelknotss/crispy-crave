import { redirect } from "next/navigation";
import { createClient, isSupabaseConfigured } from "@/lib/supabase/server";
import { fetchStaffProfile } from "@/lib/supabase/profile-role";

export type StaffSession = {
  userId: string;
  email: string | null;
  displayName: string;
  role: "admin" | "restaurant";
  restaurantId: number | null;
};

export async function getStaffSession(): Promise<StaffSession | null> {
  if (!isSupabaseConfigured()) {
    return null;
  }

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return null;

  let profile = (await fetchStaffProfile(user.id)).profile;

  if (!profile) {
    const { data } = await supabase
      .from("profiles")
      .select("display_name, role, restaurant_id")
      .eq("id", user.id)
      .maybeSingle();
    profile = data as typeof profile;
  }

  if (!profile) return null;

  const role = profile.role as string;
  if (role !== "admin" && role !== "restaurant") return null;

  const restaurantId = profile.restaurant_id as number | null;
  const displayName =
    (profile.display_name as string | null)?.trim() || user.email?.split("@")[0] || "Staff";

  return {
    userId: user.id,
    email: user.email ?? null,
    displayName,
    role: role as "admin" | "restaurant",
    restaurantId: typeof restaurantId === "number" && Number.isFinite(restaurantId)
      ? restaurantId
      : null,
  };
}

export async function requireStaff(): Promise<StaffSession> {
  const s = await getStaffSession();
  if (!s) {
    redirect("/admin/login?required=1");
  }
  return s;
}

/** Shop scope for restaurant managers; null = platform (all shops). */
export function staffShopId(staff: StaffSession): number | null {
  if (staff.role === "admin") return null;
  return staff.restaurantId;
}
