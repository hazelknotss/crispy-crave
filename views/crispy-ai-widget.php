<?php
if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}
require_once dirname(__DIR__) . '/app/crispy_ai_faq.php';

$kkCrispyAiFaqs = kk_crispy_ai_faqs();
$kkCrispyAiLogo = app_brand_logo_url();
$kkCrispyAiGreeting = kk_crispy_ai_greeting();
$kkCrispyAiOffTopic = kk_crispy_ai_off_topic();
$kkCrispyAiThanks = kk_crispy_ai_thanks();
$kkCrispyAiClarify = kk_crispy_ai_clarify();
?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/crispy-ai.css'), ENT_QUOTES, 'UTF-8') ?>">

<div class="crispy-ai" id="crispyAiRoot" aria-live="polite">
    <button type="button" class="crispy-ai__launcher" id="crispyAiLauncher" aria-expanded="false" aria-controls="crispyAiPanel">
        <img src="<?= htmlspecialchars($kkCrispyAiLogo, ENT_QUOTES, 'UTF-8') ?>" alt="" class="crispy-ai__launcher-img" width="32" height="32">
        <span class="crispy-ai__launcher-label">Crispy AI</span>
    </button>

    <div class="crispy-ai__panel" id="crispyAiPanel" hidden>
        <header class="crispy-ai__head">
            <img src="<?= htmlspecialchars($kkCrispyAiLogo, ENT_QUOTES, 'UTF-8') ?>" alt="" class="crispy-ai__avatar" width="36" height="36">
            <div class="crispy-ai__head-text">
                <p class="crispy-ai__name">Crispy AI</p>
                <p class="crispy-ai__status">FAQ assistant</p>
            </div>
            <button type="button" class="crispy-ai__close" id="crispyAiClose" aria-label="Close chat">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="crispy-ai__messages" id="crispyAiMessages" role="log" aria-relevant="additions"></div>

        <div class="crispy-ai__chips" id="crispyAiChips" aria-label="Suggested questions"></div>

        <form class="crispy-ai__form" id="crispyAiForm">
            <label class="visually-hidden" for="crispyAiInput">Message Crispy AI</label>
            <input type="text" id="crispyAiInput" class="crispy-ai__input" placeholder="Ask about ordering, profile, delivery…" autocomplete="off" maxlength="280">
            <button type="submit" class="crispy-ai__send" aria-label="Send">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</div>

<script>
window.kkCrispyAiConfig = <?= json_encode([
    'faqs' => $kkCrispyAiFaqs,
    'greeting' => $kkCrispyAiGreeting,
    'offTopic' => $kkCrispyAiOffTopic,
    'thanks' => $kkCrispyAiThanks,
    'clarify' => $kkCrispyAiClarify,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= htmlspecialchars(app_url('js/crispy-ai.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
