import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { MyOrdersView } from "@/components/store/my-orders-view";
import { CancelOrderModal } from "@/components/store/cancel-order-modal";
import type { CustomerOrderRow } from "@/lib/customer-orders";

type Search = Promise<{
  cancelled?: string;
  cancel_error?: string;
}>;

export default async function MyOrdersPage({ searchParams }: { searchParams: Search }) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/?login=required");

  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", user.id)
    .maybeSingle();

  const role = profile?.role as string | undefined;
  if (role === "admin" || role === "restaurant") redirect("/admin");
  if (role === "rider") redirect("/rider");

  const { data: raw, error } = await supabase
    .from("orders")
    .select(
      "id, customer_id, customer_display_name, shop_id, total, payment_method, payment_status, order_status, delivery_status, delivery_address, barangay, rider_id, cancel_reason, created_at, restaurants(name)"
    )
    .eq("customer_id", user.id)
    .order("created_at", { ascending: false });

  if (error) {
    return (
      <>
        <main className="my-orders-page">
          <div className="my-orders-page__inner">
            <h1 className="my-orders-page__title">My orders</h1>
            <p className="alert alert-warning">
              Could not load orders. Run <code>supabase/fix-customer-orders.sql</code> in the SQL
              Editor, then refresh.
            </p>
          </div>
        </main>
        <CancelOrderModal />
      </>
    );
  }

  const orders = (raw as unknown as CustomerOrderRow[] | null) ?? [];

  const sp = await searchParams;
  let flash: { kind: "success" | "warning"; message: string } | null = null;
  if (sp.cancelled === "1") {
    flash = { kind: "success", message: "Your order has been cancelled." };
  } else if (sp.cancel_error === "not_allowed") {
    flash = { kind: "warning", message: "That order can no longer be cancelled." };
  } else if (sp.cancel_error === "note") {
    flash = { kind: "warning", message: "Please add a note when you select Other." };
  } else if (sp.cancel_error) {
    flash = { kind: "warning", message: "Could not cancel. Please try again." };
  }

  return (
    <>
      <MyOrdersView orders={orders} flash={flash} />
      <CancelOrderModal />
    </>
  );
}
