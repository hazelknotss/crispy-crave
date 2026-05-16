import Link from "next/link";

function lastUpdated(): string {
  return new Date().toLocaleDateString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

export default function PrivacyPage() {
  return (
    <main className="legal-page">
      <div className="container legal-page__inner">
        <header className="legal-page__intro">
          <p className="legal-page__kicker">Legal</p>
          <h1 className="legal-page__title">Privacy policy</h1>
          <p className="legal-page__updated text-muted small mb-0">
            Last updated: {lastUpdated()}
          </p>
        </header>

        <div className="legal-page__surface">
          <p>
            Crispy Crave (&ldquo;we&rdquo;, &ldquo;us&rdquo;) runs this ordering site for local partner
            kitchens. This policy explains what we collect and how we use it.
          </p>

          <h2 className="h6 fw-semibold mt-4">Information we collect</h2>
          <ul>
            <li>
              <strong>Account details</strong> — name, email, and password (stored securely) when
              you register.
            </li>
            <li>
              <strong>Order details</strong> — items, shop, delivery or pickup preferences, address or
              notes, and payment method you choose at checkout.
            </li>
            <li>
              <strong>Technical data</strong> — basic logs such as browser type and pages visited, used
              to keep the service reliable.
            </li>
          </ul>

          <h2 className="h6 fw-semibold mt-4">How we use it</h2>
          <ul>
            <li>To process and fulfill your orders with the kitchen you selected.</li>
            <li>To show your order history in &ldquo;My orders&rdquo;.</li>
            <li>To improve the site and respond to support requests.</li>
          </ul>

          <h2 className="h6 fw-semibold mt-4">Sharing</h2>
          <p>
            We share order information only with the restaurant fulfilling your order and as needed to
            operate payments or delivery. We do not sell your personal data.
          </p>

          <h2 className="h6 fw-semibold mt-4">Your choices</h2>
          <p>
            You can update account details by contacting us. You may request deletion of your account
            using the phone number on our site.
          </p>

          <h2 className="h6 fw-semibold mt-4">Contact</h2>
          <p className="mb-0">
            Questions about privacy? Call{" "}
            <a href="tel:+639389762763">09389762763</a> during business hours (10AM – 10PM).
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
