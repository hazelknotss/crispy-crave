<?php
/** @var array<string, mixed> $kkPicksConfig */
if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}
?>
<section
    class="crispy-picks"
    id="crispyPicks"
    aria-labelledby="crispy-picks-heading"
    data-weather="cloudy">
    <div class="crispy-picks__inner">
        <div class="crispy-picks__aura" aria-hidden="true"></div>
        <header class="crispy-picks__head">
            <div class="crispy-picks__icon" aria-hidden="true">
                <i class="bi bi-cloud-sun"></i>
            </div>
            <div class="crispy-picks__head-text">
                <p class="crispy-picks__eyebrow">Crispy Picks</p>
                <h2 id="crispy-picks-heading" class="crispy-picks__title">Perfect for today’s weather</h2>
                <p class="crispy-picks__context" id="crispyPicksContext">Checking weather near you…</p>
            </div>
        </header>

        <div class="crispy-picks__grid" id="crispyPicksGrid" role="list" aria-live="polite"></div>

        <p class="crispy-picks__note" id="crispyPicksNote">
            Suggestions match your local weather and items from our kitchens.
        </p>
    </div>
</section>

<script type="application/json" id="kkCrispyPicksConfig"><?= json_encode(
    $kkPicksConfig,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
) ?></script>
<script src="<?= htmlspecialchars(app_url('js/crispy-picks.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
