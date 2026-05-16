import type { NextConfig } from "next";
import withPWAInit from "@ducanh2912/next-pwa";

const withPWA = withPWAInit({
  dest: "public",
  /** Set DISABLE_PWA=1 in Vercel env to rule out service-worker cache after deploys. */
  disable:
    process.env.NODE_ENV === "development" || process.env.DISABLE_PWA === "1",
  register: true,
  scope: "/",
});

const nextConfig: NextConfig = {
  reactStrictMode: true,
  async redirects() {
    return [
      { source: "/my-orders.php", destination: "/my-orders", permanent: true },
      { source: "/profile.php", destination: "/profile", permanent: true },
      { source: "/cart.php", destination: "/cart", permanent: true },
      { source: "/checkout.php", destination: "/checkout", permanent: true },
      { source: "/login.php", destination: "/?login=required", permanent: false },
    ];
  },
};

export default withPWA(nextConfig);
