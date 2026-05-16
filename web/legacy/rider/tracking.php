<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

requireRider();
kk_rider_ensure_schema($pdo);
$riderId = (int) $_SESSION['user']['id'];
$loc = kk_rider_last_location($pdo, $riderId);
$kkRiderNavActive = 'tracking';
$riderPageTitle = 'GPS tracking';
require '../views/rider-layout-head.php';
$mapUrl = $loc
    ? 'https://www.google.com/maps?q=' . urlencode($loc['lat'] . ',' . $loc['lng'])
    : '';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-hero">
            <div class="rider-dash-hero__copy">
                <p class="rider-dash-header__kicker">Fleet</p>
                <h1 class="rider-dash-header__title">Live location</h1>
                <p class="rider-dash-header__lede">Share your GPS so dispatch can monitor deliveries. Updates every 30 seconds while this page is open.</p>
            </div>
        </header>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="rider-dash-surface p-3 p-md-4">
                    <p class="small text-muted mb-2">Status</p>
                    <p id="gpsStatus" class="fw-semibold mb-3">Waiting for location…</p>
                    <button type="button" class="btn btn-dark w-100 mb-2" id="gpsStart">Start sharing location</button>
                    <button type="button" class="btn btn-outline-secondary w-100" id="gpsStop" disabled>Stop</button>
                    <?php if ($loc): ?>
                        <p class="small text-muted mt-3 mb-1">Last update: <?= htmlspecialchars($loc['updated_at'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($mapUrl !== ''): ?>
                            <a href="<?= htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success w-100 mt-2">View on map</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="rider-dash-surface p-3 p-md-4">
                    <h2 class="h6 fw-bold">How it works</h2>
                    <ul class="small text-muted mb-0">
                        <li>Allow location access when prompted.</li>
                        <li>Keep this tab open during active deliveries.</li>
                        <li>Kitchen managers can use your last known point for dispatch.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    var watchId = null;
    var statusEl = document.getElementById('gpsStatus');
    var startBtn = document.getElementById('gpsStart');
    var stopBtn = document.getElementById('gpsStop');

    function setStatus(msg) { if (statusEl) statusEl.textContent = msg; }

    function sendPosition(pos) {
        var fd = new FormData();
        fd.append('lat', String(pos.coords.latitude));
        fd.append('lng', String(pos.coords.longitude));
        fd.append('accuracy', String(pos.coords.accuracy || ''));
        fetch('save-location.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) setStatus('Location shared · ' + new Date().toLocaleTimeString());
            })
            .catch(function () { setStatus('Could not save location'); });
    }

    if (startBtn) {
        startBtn.addEventListener('click', function () {
            if (!navigator.geolocation) { setStatus('Geolocation not supported'); return; }
            watchId = navigator.geolocation.watchPosition(sendPosition, function () {
                setStatus('Location permission denied');
            }, { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 });
            startBtn.disabled = true;
            stopBtn.disabled = false;
            setStatus('Sharing location…');
        });
    }
    if (stopBtn) {
        stopBtn.addEventListener('click', function () {
            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
            watchId = null;
            startBtn.disabled = false;
            stopBtn.disabled = true;
            setStatus('Stopped');
        });
    }
})();
</script>

<?php require '../views/rider-layout-foot.php'; ?>
