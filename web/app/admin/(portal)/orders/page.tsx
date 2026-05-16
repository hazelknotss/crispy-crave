import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { requireStaff, staffShopId } from "@/lib/staff-session";
import { AssignRiderForm } from "@/components/admin/assign-rider-form";

type OrderRow = {
  id: number;
  customer_display_name: string;
  shop_id: number;
  total: number;
  payment_method: string;
  payment_status: string;
  order_status: string;
  delivery_status: string;
  rider_id: string | null;
  created_at: string;
  restaurants: { name: string } | null;
};

export default async function AdminOrdersPage() {
  const staff = await requireStaff();
  const supabase = await createClient();
  const shopScope = staffShopId(staff);

  const { data: raw } = await supabase
    .from("orders")
    .select(
      "id, customer_display_name, shop_id, total, payment_method, payment_status, order_status, delivery_status, rider_id, created_at, restaurants(name)"
    )
    .order("created_at", { ascending: false })
    .limit(200);

  let orders = (raw as unknown as OrderRow[] | null) ?? [];
  if (shopScope !== null) {
    orders = orders.filter((o) => o.shop_id === shopScope);
  }

  let riders: { id: string; display_name: string | null }[] = [];
  if (shopScope !== null) {
    const { data: r } = await supabase
      .from("profiles")
      .select("id, display_name")
      .eq("role", "rider")
      .eq("approval_status", "approved")
      .eq("restaurant_id", shopScope);
    riders = (r as { id: string; display_name: string | null }[] | null) ?? [];
  }

  return (
    <>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">
          {shopScope !== null ? "Shop orders" : "All orders"}
        </h1>
        <p className="staff-page-head__sub">Status, riders, and details</p>
      </header>

      <section className="staff-panel">
        <div className="staff-panel__body staff-table-wrap">
          <table className="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                {shopScope === null ? <th>Shop</th> : null}
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                {shopScope !== null ? <th>Delivery</th> : null}
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {orders.length === 0 ? (
                <tr>
                  <td
                    colSpan={shopScope === null ? 7 : 7}
                    className="text-center text-muted py-4"
                  >
                    No orders found
                  </td>
                </tr>
              ) : (
                orders.map((o) => (
                  <tr key={o.id}>
                    <td>{o.id}</td>
                    <td>{o.customer_display_name}</td>
                    {shopScope === null ? (
                      <td>{o.restaurants?.name ?? "—"}</td>
                    ) : null}
                    <td>₱{Number(o.total).toFixed(2)}</td>
                    <td>
                      {o.payment_method.toUpperCase()}
                      <br />
                      <span
                        className={`badge ${
                          o.payment_status === "paid" ? "bg-success" : "bg-warning text-dark"
                        }`}
                      >
                        {o.payment_status.toUpperCase()}
                      </span>
                    </td>
                    <td>
                      <span className="badge bg-secondary">{o.order_status}</span>
                    </td>
                    {shopScope !== null ? (
                      <td>
                        <span className="badge bg-secondary">
                          {o.delivery_status.replace(/_/g, " ")}
                        </span>
                      </td>
                    ) : null}
                    <td>
                      <div className="d-flex flex-wrap gap-2 align-items-center">
                        <Link
                          href={`/admin/orders/${o.id}`}
                          className="btn btn-sm btn-outline-primary"
                        >
                          View
                        </Link>
                        {shopScope !== null ? (
                          <AssignRiderForm
                            orderId={o.id}
                            riders={riders}
                            currentRiderId={o.rider_id}
                          />
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </section>
    </>
  );
}
