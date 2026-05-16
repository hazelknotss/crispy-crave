import Link from "next/link";
import { redirect } from "next/navigation";
import { requireStaff } from "@/lib/staff-session";

export default async function AdminStatsPage() {
  const staff = await requireStaff();
  if (staff.role !== "admin") {
    redirect("/admin");
  }

  return (
    <>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">Stats</h1>
        <p className="staff-page-head__sub">
          Platform analytics from legacy <code>admin/admin_stats.php</code> will land here.
        </p>
      </header>
      <Link href="/admin" className="staff-btn staff-btn--secondary">
        ← Dashboard
      </Link>
    </>
  );
}
