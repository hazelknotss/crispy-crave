"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { createMenuItem, updateMenuItem } from "@/app/admin/actions";

type Menu = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  image: string | null;
};

export function MenuItemEditor({
  mode,
  shopId,
  shopName,
  menu,
}: {
  mode: "create" | "edit";
  shopId: number;
  shopName: string;
  menu?: Menu;
}) {
  const router = useRouter();
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setErr(null);
    setLoading(true);
    const fd = new FormData(e.currentTarget);
    const res =
      mode === "create" ? await createMenuItem(fd) : await updateMenuItem(fd);
    setLoading(false);
    if (res && "error" in res && res.error) {
      setErr(res.error);
      return;
    }
    router.push(`/admin/menus?shop_id=${shopId}`);
    router.refresh();
  }

  return (
    <>
      <div className="mb-3">
        <Link href={`/admin/menus?shop_id=${shopId}`} className="staff-chip staff-chip--menus text-decoration-none">
          ← {shopName} menus
        </Link>
      </div>
      <header className="staff-page-head">
        <h1 className="staff-page-head__title">
          {mode === "create" ? "Add menu item" : "Edit menu item"}
        </h1>
      </header>

      {err ? (
        <div className="alert alert-danger" role="alert">
          {err}
        </div>
      ) : null}

      <form className="staff-panel staff-panel__body p-3" onSubmit={onSubmit} style={{ maxWidth: 480 }}>
        <input type="hidden" name="shop_id" value={shopId} />
        {mode === "edit" && menu ? <input type="hidden" name="menu_id" value={menu.id} /> : null}
        <div className="mb-3">
          <label className="form-label" htmlFor="m-name">
            Name
          </label>
          <input
            id="m-name"
            name="name"
            className="form-control"
            required
            defaultValue={menu?.name ?? ""}
          />
        </div>
        <div className="mb-3">
          <label className="form-label" htmlFor="m-desc">
            Description
          </label>
          <textarea
            id="m-desc"
            name="description"
            className="form-control"
            rows={3}
            defaultValue={menu?.description ?? ""}
          />
        </div>
        <div className="mb-3">
          <label className="form-label" htmlFor="m-price">
            Price (₱)
          </label>
          <input
            id="m-price"
            name="price"
            type="number"
            step="0.01"
            min="0"
            className="form-control"
            required
            defaultValue={menu ? String(menu.price) : ""}
          />
        </div>
        <div className="mb-3">
          <label className="form-label" htmlFor="m-img">
            Image path
          </label>
          <input
            id="m-img"
            name="image"
            className="form-control"
            placeholder="e.g. crispy_king/chicken.jpg"
            defaultValue={menu?.image ?? ""}
          />
          <p className="small text-muted mt-1">
            Relative to <code>images/menus/</code> (same as storefront).
          </p>
        </div>
        <button type="submit" className="staff-btn staff-btn--primary" disabled={loading}>
          {loading ? "Saving…" : mode === "create" ? "Create item" : "Save changes"}
        </button>
      </form>
    </>
  );
}
