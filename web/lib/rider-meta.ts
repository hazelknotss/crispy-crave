export function riderDeliveryStatusMeta(status: string): { className: string; label: string } {
  switch (status) {
    case "picked_up":
      return { className: "rider-pill--picked", label: "Picked up" };
    case "on_the_way":
      return { className: "rider-pill--way", label: "On the way" };
    case "delivered":
      return { className: "rider-pill--done", label: "Delivered" };
    default:
      return { className: "rider-pill--assigned", label: "Assigned" };
  }
}

export function riderOrderStatusMeta(status: string): { className: string; label: string } {
  switch (status.toLowerCase()) {
    case "preparing":
      return { className: "rider-pill--prep", label: "Preparing" };
    case "delivering":
      return { className: "rider-pill--way", label: "Delivering" };
    case "completed":
      return { className: "rider-pill--done", label: "Completed" };
    case "cancelled":
      return { className: "rider-pill--muted", label: "Cancelled" };
    default:
      return { className: "rider-pill--pending", label: "Pending" };
  }
}
