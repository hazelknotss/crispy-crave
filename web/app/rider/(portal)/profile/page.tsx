import { RiderStubShell } from "@/components/rider/rider-stub-shell";
import Link from "next/link";

export default async function RiderProfilePage() {
  return (
    <RiderStubShell title="Profile">
      <p className="text-muted small">
        Account details use Supabase Auth + <code>profiles</code>. Edit display name from the{" "}
        <Link href="/profile">customer profile</Link> page for now, or ask an admin to update your
        rider row.
      </p>
    </RiderStubShell>
  );
}
