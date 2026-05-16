import { RiderStubShell } from "@/components/rider/rider-stub-shell";

export default async function RiderPerformancePage() {
  return (
    <RiderStubShell title="Stats">
      <p className="text-muted small mb-0">
        Performance charts from <code>rider/performance.php</code> are not wired to Supabase yet.
      </p>
    </RiderStubShell>
  );
}
