import Link from "next/link";

function lastUpdated(): string {
  return new Date().toLocaleDateString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

export default function TermsPage() {
  return (
    <main className="legal-page">
      <div className="container legal-page__inner">
        <header className="legal-page__intro">
          <p className="legal-page__kicker">Legal</p>
          <h1 className="legal-page__title">Terms of service</h1>
          <p className="legal-page__updated text-muted small mb-0">
            Last updated: {lastUpdated()}
          </p>
        </header>

        <div className="legal-page__surface">
          <p>
            By using Crispy Crave to browse menus and place orders, you agree to these terms. If you
            do not agree, please do not use the service.
          </p>

          <h2 className="h6 fw-semibold mt-4">Ordering</h2>
          <ul>
            <li>
              Each cart is limited to <strong>one kitchen</strong> at a time.
            </li>
            <li>
              Prices, availability, and delivery times are set by each partner kitchen and may
              change.
            </li>
            <li>
              Placing an order is an offer to purchase; the kitchen confirms fulfillment when your
              order is accepted and prepared.
            </li>
          </ul>

          <h2 className="h6 fw-semibold mt-4">Accounts</h2>
          <p>
            You are responsible for keeping your login details secure and for activity under your
            account. Provide accurate contact and delivery information so we can reach you about your
            order.
          </p>

          <h2 className="h6 fw-semibold mt-4">Payments</h2>
          <p>
            Payment options shown at checkout (such as cash on delivery or bank transfer) must be
            completed as selected. Unpaid or fraudulent orders may be cancelled.
          </p>

          <h2 className="h6 fw-semibold mt-4">Cancellations & issues</h2>
          <p>
            Contact us promptly if there is a problem with your order. Refunds or replacements are
            handled according to each kitchen&apos;s policy and applicable law.
          </p>

          <h2 className="h6 fw-semibold mt-4">Limitation</h2>
          <p className="mb-0">
            Crispy Crave provides the ordering platform. Food preparation and delivery are the
            responsibility of the kitchen you order from. The service is provided &ldquo;as
            is&rdquo; to the extent permitted by law.
          </p>
        </div>

        <p className="legal-page__back mt-4 mb-0">
          <Link href="/" className="fw-semibold text-decoration-none">
            ← Back to home
          </Link>
        </p>
      </div>
    </main>
  );
}
