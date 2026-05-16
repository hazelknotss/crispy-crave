import Link from "next/link";
import { redirect } from "next/navigation";
import { requireStaff } from "@/lib/staff-session";

export default async function AdminShopsPage() {
  const staff = await requireStaff();
  if (staff.role !== "admin") {
    redirect("/admin");
  }

  return (
    <>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">Shops</h1>
        <p className="staff-page-head__sub">
          Legacy <code>admin/shop.php</code>, add/edit shop — full CRUD will use Supabase{" "}
          <code>restaurants</code> here.
        </p>
      </header>
      <Link href="/admin" className="staff-btn staff-btn--secondary">
        ← Dashboard
      </Link>
    </>
  );
}
