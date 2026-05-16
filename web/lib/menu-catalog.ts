import type { SupabaseClient } from "@supabase/supabase-js";

export type CatalogItem = {
  id: number;
  shopId: number;
  shopName: string;
  name: string;
  description: string;
  price: number;
  image: string;
  category: string;
  tags: string[];
};

function resolveCategory(name: string, stored: string | null | undefined): string {
  const s = (stored ?? "").trim();
  if (s !== "") return s;

  const n = name.toLowerCase();
  if (/\b(chicken|wings|drumstick)\b/.test(n)) return "Chicken";
  if (/\b(rice|pares|silog)\b/.test(n)) return "Rice meals";
  if (/\b(sioma|lumpia|kikiam|tempura|ngohiong|bola|siopao|dim\s*sum)\b/.test(n))
    return "Dim sum";
  if (/\b(fries|cheese|nuts|snack|sticks)\b/.test(n)) return "Sides & snacks";
  if (/\b(drink|juice|soda|tea|coffee|water)\b/.test(n)) return "Drinks";
  return "General";
}

export function menuItemRecommendationTags(
  name: string,
  category: string,
  description: string
): string[] {
  const blob = `${name} ${category} ${description}`.toLowerCase();
  const tags: string[] = [];

  const rules: [string, RegExp][] = [
    ["chicken", /\b(chicken|wings|drumstick)\b/],
    ["fried", /\b(fried|crispy|crunch)\b/],
    ["rice", /\b(rice|with rice)\b/],
    ["pares", /\bpares\b/],
    ["soup", /\b(broth|soup|pares)\b/],
    ["warm", /\b(steamed|hot|slow-cooked|broth|siopao|pares|savory)\b/],
    ["comfort", /\b(pares|rice|siopao|hearty)\b/],
    ["dimsum", /\b(sioma|lumpia|kikiam|tempura|ngohiong|bola|siopao|dim\s*sum)\b/],
    ["snack", /\b(lumpia|kikiam|cheese|fries|nuts|sticks|tempura|street|skewer|roll)\b/],
    ["siomai", /\bsioma/i],
    ["lumpia", /\blumpia/i],
    ["light", /\b(3 pcs|snack|nuts|small|bite)\b/i],
    ["street", /\b(street|kikiam|cheese sticks|fries|tempura)\b/],
    ["share", /\b(3 pcs|sticks|fries|lumpia|bola)\b/],
  ];

  for (const [tag, pattern] of rules) {
    if (pattern.test(blob)) tags.push(tag);
  }

  const cat = category.toLowerCase();
  if (cat === "dim sum") {
    tags.push("dimsum", "snack");
  } else if (cat === "chicken") {
    tags.push("chicken", "fried");
  } else if (cat === "rice meals") {
    tags.push("rice", "comfort");
  } else if (cat === "sides & snacks") {
    tags.push("snack", "street");
  }

  return [...new Set(tags)];
}

export async function buildMenuCatalogForPicks(
  supabase: SupabaseClient
): Promise<CatalogItem[]> {
  const [{ data: menus }, { data: rests }] = await Promise.all([
    supabase
      .from("menus")
      .select("id, restaurant_id, name, description, price, image, category")
      .eq("is_active", true),
    supabase.from("restaurants").select("id, name").eq("is_active", true),
  ]);

  if (!menus?.length) return [];

  const nameById = new Map<number, string>();
  for (const r of rests ?? []) {
    nameById.set(Number((r as { id: number }).id), String((r as { name: string }).name));
  }

  const catalog: CatalogItem[] = [];
  for (const row of menus as Record<string, unknown>[]) {
    const shopId = Number(row.restaurant_id);
    const shopName = nameById.get(shopId) ?? "";
    const name = String(row.name ?? "");
    const category = resolveCategory(
      name,
      row.category != null ? String(row.category) : null
    );
    const description = String(row.description ?? "");
    catalog.push({
      id: Number(row.id),
      shopId,
      shopName,
      name,
      description,
      price: Number(row.price),
      image: String(row.image ?? ""),
      category,
      tags: menuItemRecommendationTags(name, category, description),
    });
  }

  return catalog.sort((a, b) =>
    a.shopId !== b.shopId ? a.shopId - b.shopId : a.id - b.id
  );
}

export type ShopRow = {
  id: number;
  name: string;
  description: string | null;
  logo: string | null;
  delivery_time: string | null;
};

export type MenuRow = {
  id: number;
  restaurant_id: number;
  name: string;
  description: string | null;
  price: number;
  image: string | null;
};

export async function loadHomeCatalog(
  supabase: SupabaseClient
): Promise<{
  shops: ShopRow[];
  menusByShop: Map<number, MenuRow[]>;
}> {
  const { data: shops } = await supabase
    .from("restaurants")
    .select("id, name, description, logo, delivery_time")
    .eq("is_active", true)
    .order("name", { ascending: true });

  const shopRows = (shops as ShopRow[] | null) ?? [];
  const shopIds = shopRows.map((s) => s.id);
  const menusByShop = new Map<number, MenuRow[]>();

  if (shopIds.length === 0) {
    return { shops: shopRows, menusByShop };
  }

  const { data: menus, error: e2 } = await supabase
    .from("menus")
    .select("id, restaurant_id, name, description, price, image")
    .eq("is_active", true)
    .in("restaurant_id", shopIds)
    .order("restaurant_id", { ascending: true })
    .order("id", { ascending: true });

  if (!e2 && menus) {
    for (const m of menus as MenuRow[]) {
      const list = menusByShop.get(m.restaurant_id) ?? [];
      list.push(m);
      menusByShop.set(m.restaurant_id, list);
    }
  }

  return { shops: shopRows, menusByShop };
}

/** Single restaurant + active menus (restaurant detail page). */
export async function loadRestaurantDetail(
  supabase: SupabaseClient,
  shopId: number
): Promise<{ shop: ShopRow | null; menus: MenuRow[] }> {
  const { data: shop } = await supabase
    .from("restaurants")
    .select("id, name, description, logo, delivery_time")
    .eq("id", shopId)
    .eq("is_active", true)
    .maybeSingle();

  if (!shop) {
    return { shop: null, menus: [] };
  }

  const { data: menus } = await supabase
    .from("menus")
    .select("id, restaurant_id, name, description, price, image")
    .eq("restaurant_id", shopId)
    .eq("is_active", true)
    .order("id", { ascending: true });

  return {
    shop: shop as ShopRow,
    menus: (menus as MenuRow[]) ?? [],
  };
}
