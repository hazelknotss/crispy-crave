"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const NAV: { key: string; label: string; href: string; icon: string }[] = [
  { key: "deliveries", label: "Deliveries", href: "/rider", icon: "bi-truck" },
  { key: "earnings", label: "Earnings", href: "/rider/earnings", icon: "bi-wallet2" },
  { key: "performance", label: "Stats", href: "/rider/performance", icon: "bi-bar-chart" },
  { key: "tracking", label: "GPS", href: "/rider/tracking", icon: "bi-geo-alt" },
  { key: "notifications", label: "Alerts", href: "/rider/notifications", icon: "bi-bell" },
  { key: "profile", label: "Profile", href: "/rider/profile", icon: "bi-person" },
];

export function RiderDockNav() {
  const pathname = usePathname();
  return (
    <nav className="rider-portal-nav" aria-label="Rider portal">
      <div className="rider-portal-nav__dock">
        {NAV.map((item) => {
          const active =
            item.href === "/rider"
              ? pathname === "/rider" || Boolean(pathname?.startsWith("/rider/order"))
              : pathname === item.href || Boolean(pathname?.startsWith(`${item.href}/`));
          return (
            <Link
              key={item.key}
              href={item.href}
              className={`rider-portal-nav__link${active ? " rider-portal-nav__link--active" : ""}`}
              aria-current={active ? "page" : undefined}
              title={item.label}
            >
              <span className="rider-portal-nav__icon-wrap">
                <i className={`bi ${item.icon}`} aria-hidden="true" />
              </span>
              <span className="rider-portal-nav__label">{item.label}</span>
            </Link>
          );
        })}
      </div>
    </nav>
  );
}
