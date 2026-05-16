import Link from "next/link";
import type { ReactNode } from "react";
import Script from "next/script";
import { createClient } from "@/lib/supabase/server";
import { fetchRiderProfile } from "@/lib/rider-data";
import { RiderTopbar } from "@/components/rider/rider-topbar";
import { RiderDockNav } from "@/components/rider/rider-dock-nav";

export async function RiderStubShell({
  title,
  children,
}: {
  title: string;
  children: ReactNode;
}) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  const profile = user ? await fetchRiderProfile(supabase, user.id) : null;
  const riderName =
    profile?.display_name?.trim() || user?.email?.split("@")[0] || "Rider";

  return (
    <>
      <RiderTopbar displayName={riderName} />
      <main className="rider-dash-page">
        <div className="container-fluid rider-dash-page__inner" style={{ maxWidth: 560 }}>
          <h1 className="h5 fw-bold mb-2">{title}</h1>
          {children}
          <p className="mt-4 mb-0">
            <Link href="/rider">← Deliveries</Link>
          </p>
        </div>
      </main>
      <RiderDockNav />
      <Script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        strategy="lazyOnload"
      />
    </>
  );
}
