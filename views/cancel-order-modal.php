<?php
if (!function_exists('kk_customer_cancel_reasons')) {
    require_once dirname(__DIR__) . '/app/customer_orders.php';
}
$kkCancelReasons = kk_customer_cancel_reasons();
?>
<div class="modal fade" id="kkCancelOrderModal" tabindex="-1" aria-labelledby="kkCancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kk-cancel-modal">
            <form method="post" action="<?= htmlspecialchars(app_url('cancel-order.php'), ENT_QUOTES, 'UTF-8') ?>" id="kkCancelOrderForm">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5 fw-bold" id="kkCancelOrderModalLabel">Cancel order?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="kk-cancel-modal__lede small text-muted mb-3">
                        Tell us why you want to cancel <strong id="kkCancelOrderLabel">this order</strong>. This helps us improve.
                    </p>
                    <input type="hidden" name="order_id" id="kkCancelOrderId" value="">
                    <input type="hidden" name="redirect" id="kkCancelOrderRedirect" value="">

                    <fieldset class="kk-cancel-modal__reasons mb-3">
                        <legend class="visually-hidden">Cancellation reason</legend>
                        <?php foreach ($kkCancelReasons as $key => $label): ?>
                            <label class="kk-cancel-modal__reason">
                                <input type="radio" name="cancel_reason" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" required<?= $key === 'changed_mind' ? ' checked' : '' ?>>
                                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <div class="kk-cancel-modal__note-wrap" id="kkCancelNoteWrap" hidden>
                        <label for="kkCancelNote" class="form-label small fw-semibold">Please tell us more</label>
                        <textarea
                            id="kkCancelNote"
                            name="cancel_note"
                            class="form-control form-control-sm"
                            rows="3"
                            maxlength="400"
                            placeholder="Optional details…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 flex-nowrap gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Keep order</button>
                    <button type="submit" class="btn btn-danger flex-fill">Cancel order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function initCancelOrderModal() {
    var modalEl = document.getElementById('kkCancelOrderModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var form = document.getElementById('kkCancelOrderForm');
    var orderIdInput = document.getElementById('kkCancelOrderId');
    var redirectInput = document.getElementById('kkCancelOrderRedirect');
    var labelEl = document.getElementById('kkCancelOrderLabel');
    var noteWrap = document.getElementById('kkCancelNoteWrap');
    var noteInput = document.getElementById('kkCancelNote');

    function syncNoteVisibility() {
        var other = form && form.querySelector('input[name="cancel_reason"][value="other"]:checked');
        if (noteWrap) noteWrap.hidden = !other;
        if (noteInput) {
            noteInput.required = !!other;
            if (!other) noteInput.value = '';
        }
    }

    document.querySelectorAll('[data-kk-cancel-order]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-kk-cancel-order');
            var redirect = btn.getAttribute('data-kk-cancel-redirect') || 'my-orders.php';
            if (orderIdInput) orderIdInput.value = id || '';
            if (redirectInput) redirectInput.value = redirect;
            if (labelEl) labelEl.textContent = id ? ('order #' + id) : 'this order';
            syncNoteVisibility();
            modal.show();
        });
    });

    if (form) {
        form.querySelectorAll('input[name="cancel_reason"]').forEach(function (radio) {
            radio.addEventListener('change', syncNoteVisibility);
        });
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to cancel this order?')) {
                e.preventDefault();
            }
        });
    }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCancelOrderModal);
    } else {
        initCancelOrderModal();
    }
})();
</script>
