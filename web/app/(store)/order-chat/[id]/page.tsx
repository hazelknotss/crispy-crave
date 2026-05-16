import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";

export default async function OrderChatPage({ params }: { params: Promise<{ id: string }> }) {
  const orderId = parseInt((await params).id, 10);
  if (!Number.isFinite(orderId) || orderId < 1) notFound();

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/?login=required");

  const { data: order } = await supabase
    .from("orders")
    .select("id, rider_id")
    .eq("id", orderId)
    .eq("customer_id", user.id)
    .maybeSingle();

  if (!order) notFound();
  if (!order.rider_id) {
    redirect(`/order-track/${orderId}`);
  }

  return (
    <main className="order-chat-page order-chat-page--customer section-inner py-5">
      <Link href={`/order-track/${orderId}`} className="text-decoration-none small">
        ← Back to order
      </Link>
      <h1 className="h4 fw-bold mt-3">Chat — Order #{orderId}</h1>
      <p className="text-muted">
        Rider messaging UI is next. Your order is assigned; use Track order for status updates.
      </p>
    </main>
  );
}
