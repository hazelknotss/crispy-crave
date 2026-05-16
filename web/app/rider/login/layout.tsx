import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { fetchRiderProfile } from "@/lib/rider-data";

export default async function RiderLoginChromeLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (user) {
    const profile = await fetchRiderProfile(supabase, user.id);
    if (profile?.role === "rider" && profile.approval_status === "approved") {
      redirect("/rider");
    }
  }

  return <div className="rider-login-page">{children}</div>;
}
