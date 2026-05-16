/** Build Google Maps query string (mirrors PHP kk_maps_destination). */
export function mapsDestination(deliveryAddress: string, barangay: string): string {
  let block = deliveryAddress.trim();
  const nl = /^([\s\S]+?)(?:\r\n|\n)\s*(?:\r\n|\n)/.exec(block);
  if (nl) block = nl[1]!.trim();

  const lines = block.split(/\r\n|\r|\n/);
  let street = (lines[0] ?? block).trim();
  street = street.replace(
    /\s*(Scheduled delivery|Delivery option|Preferred payment|Customer notes|Fulfillment:).*$/iu,
    ""
  );
  street = street.replace(/[,;\s]+$/g, "").trim();
  if (street === "") street = block.trim();

  const parts: string[] = [];
  if (street !== "") parts.push(street);

  const b = barangay.trim();
  if (b !== "" && !/pickup/i.test(b)) {
    const lower = street.toLowerCase();
    if (!lower.includes(b.toLowerCase())) parts.push(b);
  }

  parts.push("Pototan", "Iloilo", "Philippines");
  return [...new Set(parts.filter(Boolean))].join(", ");
}
