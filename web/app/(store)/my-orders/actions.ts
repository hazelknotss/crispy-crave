"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { CANCEL_REASONS, customerCanCancel } from "@/lib/customer-orders";

export async function cancelCustomerOrder(formData: FormData): Promise<void> {
  const orderId = parseInt(String(formData.get("order_id") ?? ""), 10);
  const reasonKey = String(formData.get("cancel_reason") ?? "");
  const note = String(formData.get("cancel_note") ?? "").trim();
  const redirectTo = String(formData.get("redirect") ?? "/my-orders");

  const safeRedirect =
    redirectTo.startsWith("/my-orders") || redirectTo.startsWith("/order-track/")
      ? redirectTo
      : "/my-orders";

  if (!Number.isFinite(orderId) || orderId < 1 || !CANCEL_REASONS[reasonKey]) {
    redirect(`${safeRedirect}?cancel_error=invalid`);
  }

  if (reasonKey === "other" && !note) {
    redirect(`${safeRedirect}?cancel_error=note`);
  }

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/?login=required");

  const { data: order } = await supabase
    .from("orders")
    .select("id, order_status, delivery_status, customer_id")
    .eq("id", orderId)
    .eq("customer_id", user.id)
    .maybeSingle();

  if (!order || !customerCanCancel(order)) {
    redirect(`${safeRedirect}?cancel_error=not_allowed`);
  }

  let reasonText = CANCEL_REASONS[reasonKey]!;
  if (note) reasonText += ` — ${note}`;
  if (reasonText.length > 500) reasonText = `${reasonText.slice(0, 497)}...`;

  const { error } = await supabase
    .from("orders")
    .update({
      order_status: "cancelled",
      cancel_reason: reasonText,
      cancelled_at: new Date().toISOString(),
    })
    .eq("id", orderId)
    .eq("customer_id", user.id);

  if (error) {
    redirect(`${safeRedirect}?cancel_error=failed`);
  }

  revalidatePath("/my-orders");
  revalidatePath(`/order-track/${orderId}`);
  redirect(`${safeRedirect}?cancelled=1`);
}
