<?php

declare(strict_types=1);

if (!function_exists('app_base_path')) {
    require_once dirname(__DIR__) . '/app/url.php';
}

$base = app_base_path();

?>
<script>
(function () {
  if (!('serviceWorker' in navigator)) return;
  var base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var prefix = base || '';
  var swUrl = prefix + '/sw.js';
  var scope = prefix ? prefix + '/' : '/';
  navigator.serviceWorker.register(swUrl, { scope: scope }).catch(function () {});
})();
</script>
