import Link from "next/link";
import Script from "next/script";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import {
  claimRiderOrder,
  fetchRiderOrderById,
  fetchRiderProfile,
} from "@/lib/rider-data";
import { riderMarkDelivered } from "@/app/rider/actions";
import { RiderTopbar } from "@/components/rider/rider-topbar";
import { RiderDockNav } from "@/components/rider/rider-dock-nav";

type Props = { params: Promise<{ id: string }> };

export default async function RiderCompleteDeliveryPage({ params }: Props) {
  const { id: idRaw } = await params;
  const orderId = parseInt(idRaw, 10);
  if (!Number.isFinite(orderId) || orderId < 1) redirect("/rider");

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/rider/login");

  const profile = await fetchRiderProfile(supabase, user.id);
  if (!profile) redirect("/rider/login");

  await claimRiderOrder(supabase, orderId, user.id);
  const order = await fetchRiderOrderById(supabase, orderId);
  if (!order || order.rider_id !== user.id) redirect("/rider");

  if (order.delivery_status === "delivered") {
    redirect(`/rider/order/${orderId}`);
  }

  const riderName =
    profile.display_name?.trim() || user.email?.split("@")[0] || "Rider";

  return (
    <>
      <RiderTopbar displayName={riderName} />
      <main className="rider-dash-page">
        <div className="container-fluid rider-dash-page__inner" style={{ maxWidth: 520 }}>
          <Link href={`/rider/order/${orderId}`} className="rider-login-panel__back d-inline-flex mb-3">
            <i className="bi bi-arrow-left" aria-hidden="true" />
            <span>Order #{orderId}</span>
          </Link>
          <h1 className="h5 fw-bold mb-2">Mark delivery complete</h1>
          <p className="text-muted small mb-4">
            Optional: paste a public image URL as proof (full photo upload can be added later).
          </p>
          <form action={riderMarkDelivered} className="rider-dash-surface p-3 p-md-4">
            <input type="hidden" name="order_id" value={orderId} />
            <div className="mb-3">
              <label className="form-label fw-semibold" htmlFor="proof-url">
                Proof image URL (optional)
              </label>
              <input
                type="url"
                className="form-control"
                id="proof-url"
                name="delivery_proof_url"
                placeholder="https://…"
              />
            </div>
            <div className="mb-4">
              <label className="form-label fw-semibold" htmlFor="proof-note">
                Note (optional)
              </label>
              <textarea
                className="form-control"
                id="proof-note"
                name="delivery_proof_note"
                rows={2}
              />
            </div>
            <button type="submit" className="btn btn-dark w-100 fw-semibold">
              Complete delivery
            </button>
          </form>
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
