import Link from "next/link";
import { redirect } from "next/navigation";
import { requireStaff, staffShopId } from "@/lib/staff-session";

type Search = Promise<{ shop_id?: string }>;

export default async function AdminRecipesPage({ searchParams }: { searchParams: Search }) {
  const staff = await requireStaff();
  const sp = await searchParams;
  let shopId = sp.shop_id ? parseInt(sp.shop_id, 10) : NaN;

  const scope = staffShopId(staff);
  if (scope !== null) {
    if (Number.isFinite(shopId) && shopId !== scope) {
      redirect(`/admin/recipes?shop_id=${scope}`);
    }
    shopId = scope;
  }

  if (staff.role === "restaurant" && (!Number.isFinite(shopId) || shopId < 1)) {
    redirect("/admin");
  }

  return (
    <>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">Recipes</h1>
        <p className="staff-page-head__sub">
          Legacy <code>admin/recipes.php</code> — recipe builder and costing will mount here.
        </p>
      </header>
      <p className="text-muted small mb-3">
        Shop filter: <strong>{Number.isFinite(shopId) ? shopId : "— (platform)"}</strong>
      </p>
      <Link href="/admin" className="staff-btn staff-btn--secondary">
        ← Dashboard
      </Link>
    </>
  );
}
