import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { fetchRiderProfile } from "@/lib/rider-data";

export default async function RiderPortalLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/rider/login");

  const profile = await fetchRiderProfile(supabase, user.id);
  if (!profile || profile.role !== "rider" || profile.approval_status !== "approved") {
    redirect("/rider/login?error=forbidden");
  }

  return <div className="rider-dash-body">{children}</div>;
}
