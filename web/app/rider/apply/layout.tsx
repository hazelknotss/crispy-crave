import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { fetchRiderProfile } from "@/lib/rider-data";

export default async function RiderApplyLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (user) {
    const profile = await fetchRiderProfile(supabase, user.id);
    if (profile?.role === "rider") {
      redirect("/rider/login");
    }
  }

  return <div className="rider-login-page rider-login-page--apply">{children}</div>;
}
