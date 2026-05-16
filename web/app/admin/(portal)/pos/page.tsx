import Link from "next/link";

type Search = Promise<{ shop_id?: string }>;

export default async function AdminPosPage({ searchParams }: { searchParams: Search }) {
  const sp = await searchParams;
  const shopId = sp.shop_id ?? "";

  return (
    <>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">Point of sale</h1>
        <p className="staff-page-head__sub">
          Counter checkout from <code>pos.php</code> — wire menu cart + order insert next.
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
