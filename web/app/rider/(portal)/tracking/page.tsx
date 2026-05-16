import { RiderStubShell } from "@/components/rider/rider-stub-shell";

export default async function RiderTrackingPage() {
  return (
    <RiderStubShell title="GPS tracking">
      <p className="text-muted small mb-0">
        Live location sharing from <code>rider/tracking.php</code> will connect here after we add
        location APIs.
      </p>
    </RiderStubShell>
  );
}
