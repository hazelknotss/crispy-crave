import Script from "next/script";
import { redirect } from "next/navigation";
import { createClient, isSupabaseConfigured } from "@/lib/supabase/server";
import { loadRestaurantDetail } from "@/lib/menu-catalog";
import { RestaurantMenuPage } from "@/components/store/restaurant-menu";

type Props = { searchParams?: Promise<{ id?: string; error?: string }> };

export default async function RestaurantPage({ searchParams }: Props) {
  const p = (await searchParams) ?? {};
  const idRaw = p.id ?? "";
  const shopId = parseInt(idRaw, 10);

  if (!idRaw || !Number.isFinite(shopId) || shopId <= 0) {
    redirect("/");
  }

  if (!isSupabaseConfigured()) {
    return (
      <main className="section-inner py-5" style={{ maxWidth: 720, margin: "0 auto" }}>
        <h1 className="h3 mb-3">Restaurant menu</h1>
        <p className="text-muted">
          Configure Supabase in <code>web/.env.local</code> to load this menu.
        </p>
      </main>
    );
  }

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { shop, menus } = await loadRestaurantDetail(supabase, shopId);
  if (!shop) {
    redirect("/");
  }

  return (
    <>
      <RestaurantMenuPage
        shop={shop}
        menus={menus}
        isLoggedIn={!!user}
        differentShopError={p.error === "different_shop"}
      />
      <Script
        src="/legacy/js/home-carousel-and-modal.js"
        strategy="afterInteractive"
      />
    </>
  );
}
