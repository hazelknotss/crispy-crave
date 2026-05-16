(function () {
    'use strict';

    var root = document.getElementById('posRoot');
    if (!root) {
        return;
    }

    var total = parseFloat(root.getAttribute('data-cart-total') || '0') || 0;
    var searchInput = document.getElementById('posSearch');
    var menuGrid = document.getElementById('posMenuGrid');
    var visibleCount = document.getElementById('posVisibleCount');
    var noResults = document.getElementById('posNoResults');
    var categoryBtns = document.querySelectorAll('.pos-cat-btn');
    var fullscreenBtn = document.getElementById('posFullscreenBtn');
    var paymentSelect = document.getElementById('posPaymentMethod');
    var cashPanel = document.getElementById('posCashPanel');
    var cashDisplay = document.getElementById('posCashDisplay');
    var changeDisplay = document.getElementById('posChangeDisplay');
    var cashHidden = document.getElementById('posCashTenderedHidden');
    var checkoutForm = document.getElementById('posCheckoutForm');
    var keypad = document.getElementById('posKeypad');

    var activeCategory = 'All';
    var cashCents = '';

    function formatMoney(amount) {
        return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function cashAmount() {
        if (!cashCents) {
            return 0;
        }
        return parseInt(cashCents, 10) / 100;
    }

    function updateCashUi() {
        var tendered = cashAmount();
        if (cashDisplay) {
            cashDisplay.textContent = formatMoney(tendered);
        }
        if (changeDisplay) {
            var change = Math.max(0, tendered - total);
            changeDisplay.textContent = formatMoney(change);
        }
        if (cashHidden) {
            cashHidden.value = tendered > 0 ? tendered.toFixed(2) : '';
        }
    }

    function setPaymentUi() {
        if (!paymentSelect || !cashPanel) {
            return;
        }
        var isCash = paymentSelect.value === 'cod';
        cashPanel.hidden = !isCash;
        if (!isCash && cashHidden) {
            cashHidden.value = '';
            cashCents = '';
            updateCashUi();
        }
    }

    function filterMenu() {
        if (!menuGrid) {
            return;
        }
        var q = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
        var items = menuGrid.querySelectorAll('.pos-menu-item');
        var shown = 0;

        items.forEach(function (el) {
            var name = el.getAttribute('data-name') || '';
            var cat = el.getAttribute('data-category') || '';
            var matchCat = activeCategory === 'All' || cat === activeCategory;
            var matchSearch = !q || name.indexOf(q) !== -1;
            var visible = matchCat && matchSearch;
            el.hidden = !visible;
            if (visible) {
                shown++;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = shown + ' shown';
        }
        if (noResults) {
            noResults.hidden = shown > 0;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterMenu);
    }

    categoryBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            categoryBtns.forEach(function (b) {
                b.classList.remove('is-active');
            });
            btn.classList.add('is-active');
            activeCategory = btn.getAttribute('data-category') || 'All';
            filterMenu();
        });
    });

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            document.body.classList.toggle('pos-fullscreen');
            var icon = fullscreenBtn.querySelector('.bi');
            if (icon) {
                icon.classList.toggle('bi-arrows-fullscreen');
                icon.classList.toggle('bi-fullscreen-exit');
            }
        });
    }

    if (paymentSelect) {
        paymentSelect.addEventListener('change', setPaymentUi);
        setPaymentUi();
    }

    if (keypad) {
        keypad.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-key]');
            if (!btn) {
                return;
            }
            var key = btn.getAttribute('data-key');
            if (key === 'clear') {
                cashCents = '';
            } else if (key === 'back') {
                cashCents = cashCents.slice(0, -1);
            } else if (key === 'exact') {
                cashCents = String(Math.round(total * 100));
            } else if (key === '00') {
                if (cashCents !== '') {
                    cashCents += '00';
                }
            } else if (/^\d$/.test(key)) {
                if (cashCents.length < 9) {
                    cashCents += key;
                }
            }
            updateCashUi();
        });
    }

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            if (paymentSelect && paymentSelect.value === 'cod') {
                var tendered = cashAmount();
                if (tendered < total - 0.001) {
                    e.preventDefault();
                    alert('Cash received must be at least the order total (' + formatMoney(total) + ').');
                    if (cashPanel) {
                        cashPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            }
        });
    }

    filterMenu();
})();
