import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import type { CustomerOrderRow } from "@/lib/customer-orders";

const ORDER_SELECT =
  "id, customer_id, customer_display_name, shop_id, total, payment_method, payment_status, order_status, delivery_status, delivery_address, barangay, rider_id, cancel_reason, created_at, restaurants(name)";

export type FetchCustomerOrdersResult = {
  orders: CustomerOrderRow[];
  loadError: string | null;
  usedAdminFallback: boolean;
};

/** Load orders for the signed-in customer (anon client, then service-role fallback). */
export async function fetchCustomerOrders(userId: string): Promise<FetchCustomerOrdersResult> {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("orders")
    .select(ORDER_SELECT)
    .eq("customer_id", userId)
    .order("created_at", { ascending: false });

  if (!error) {
    return {
      orders: (data as unknown as CustomerOrderRow[]) ?? [],
      loadError: null,
      usedAdminFallback: false,
    };
  }

  const admin = createAdminClient();
  if (!admin) {
    return {
      orders: [],
      loadError: error.message,
      usedAdminFallback: false,
    };
  }

  const { data: adminData, error: adminErr } = await admin
    .from("orders")
    .select(ORDER_SELECT)
    .eq("customer_id", userId)
    .order("created_at", { ascending: false });

  if (adminErr) {
    return { orders: [], loadError: adminErr.message, usedAdminFallback: true };
  }

  return {
    orders: (adminData as unknown as CustomerOrderRow[]) ?? [],
    loadError: null,
    usedAdminFallback: true,
  };
}
