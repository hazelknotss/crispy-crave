import Link from "next/link";

type Search = Promise<{ shop_id?: string }>;

export default async function AdminKdsPage({ searchParams }: { searchParams: Search }) {
  const sp = await searchParams;
  const shopId = sp.shop_id ?? "";

  return (
    <>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">Kitchen display (KDS)</h1>
        <p className="staff-page-head__sub">
          Live ticket board from <code>kds.php</code> will mount here — same Supabase orders as the
          rider portal.
        </p>
      </header>
      <p className="text-muted small mb-3">
        Shop filter: <strong>{shopId || "—"}</strong>
      </p>
      <Link href="/admin" className="staff-btn staff-btn--secondary">
        ← Dashboard
      </Link>
    </>
  );
}
