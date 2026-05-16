import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { requireStaff, staffShopId } from "@/lib/staff-session";
import { MenuItemEditor } from "@/components/admin/menu-item-editor";

type Search = Promise<{ shop_id?: string }>;

export default async function AdminNewMenuPage({ searchParams }: { searchParams: Search }) {
  const staff = await requireStaff();
  const sp = await searchParams;
  let shopId = sp.shop_id ? parseInt(sp.shop_id, 10) : NaN;
  const scope = staffShopId(staff);
  if (scope !== null) {
    if (Number.isFinite(shopId) && shopId !== scope) redirect(`/admin/menus/new?shop_id=${scope}`);
    shopId = scope;
  }
  if (!Number.isFinite(shopId) || shopId < 1) redirect("/admin");

  const supabase = await createClient();
  const { data: shop } = await supabase
    .from("restaurants")
    .select("name")
    .eq("id", shopId)
    .maybeSingle();
  if (!shop) redirect("/admin");

  return (
    <MenuItemEditor mode="create" shopId={shopId} shopName={shop.name as string} />
  );
}
