(function () {
    'use strict';

    var cfg = window.kkCrispyAiConfig;
    if (!cfg || !cfg.faqs) {
        return;
    }

    var root = document.getElementById('crispyAiRoot');
    var launcher = document.getElementById('crispyAiLauncher');
    var panel = document.getElementById('crispyAiPanel');
    var closeBtn = document.getElementById('crispyAiClose');
    var messagesEl = document.getElementById('crispyAiMessages');
    var chipsEl = document.getElementById('crispyAiChips');
    var form = document.getElementById('crispyAiForm');
    var input = document.getElementById('crispyAiInput');

    if (!root || !launcher || !panel || !messagesEl) {
        return;
    }

    var greeted = false;
    var lastFaqId = null;
    var typingEl = null;

    var stopWords = new Set([
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'i', 'me', 'my', 'we', 'you', 'your', 'do', 'does', 'did', 'can', 'could',
        'will', 'would', 'should', 'to', 'of', 'in', 'on', 'for', 'with', 'at',
        'it', 'this', 'that', 'and', 'or', 'but', 'if', 'how', 'what', 'when',
        'where', 'why', 'about', 'please', 'tell', 'know', 'get', 'am', 'is',
    ]);

    var domainWords = new Set([
        'order', 'orders', 'chicken', 'crispy', 'crave', 'cart', 'menu', 'food',
        'deliver', 'delivery', 'rider', 'riders', 'apply', 'application', 'job',
        'driver', 'courier', 'pay', 'payment', 'gcash', 'cod', 'cash',
        'account', 'login', 'register', 'track', 'status', 'pickup', 'shop',
        'checkout', 'cancel', 'receipt', 'privacy', 'password', 'address', 'work',
        'profile', 'settings', 'edit', 'phone', 'bank', 'card', 'details',
        'recommend', 'picks', 'weather', 'rainy', 'mood', 'craving',
    ]);

    var synonyms = {
        order: ['ordering', 'ordered', 'buy', 'purchase'],
        pay: ['payment', 'paid', 'paying', 'gcash', 'cod', 'cash'],
        deliver: ['delivery', 'delivered', 'rider', 'shipping'],
        cancel: ['cancelled', 'canceled', 'refund', 'void'],
        track: ['tracking', 'status', 'where', 'location'],
        account: ['login', 'register', 'signup', 'signin'],
        profile: ['settings', 'details', 'name', 'phone', 'gcash', 'bank', 'card'],
        phone: ['smartphone', 'mobile', 'cellphone'],
        password: ['passcode', 'pin', 'security'],
        ready: ['finished', 'done', 'prepared'],
        problem: ['issue', 'wrong', 'help', 'support', 'complaint'],
    };

    var offTopicHints = [
        'weather', 'homework', 'math', 'politics', 'football', 'basketball',
        'movie', 'song', 'recipe', 'bitcoin', 'stock', 'president', 'exam',
    ];

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatAnswer(text, faq) {
        var html = escapeHtml(text).replace(/\n/g, '<br>');
        html = html.replace(/(https?:\/\/[^\s<]+)/g, function (url) {
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });
        if (faq && faq.link && faq.link_label) {
            var safeHref = escapeHtml(faq.link);
            var safeLabel = escapeHtml(faq.link_label);
            html += '<br><br><a class="crispy-ai__cta" href="' + safeHref + '">' + safeLabel + ' →</a>';
        }
        return html;
    }

    function normalize(text) {
        return text
            .toLowerCase()
            .replace(/['']/g, "'")
            .replace(/[^a-z0-9'\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokenize(text) {
        return normalize(text)
            .split(' ')
            .filter(function (w) {
                return w.length > 1 && !stopWords.has(w);
            });
    }

    function expandTokens(words) {
        var out = new Set(words);
        words.forEach(function (w) {
            (synonyms[w] || []).forEach(function (s) {
                out.add(s);
            });
        });
        return Array.from(out);
    }

    function levenshtein(a, b) {
        if (a === b) {
            return 0;
        }
        if (a.length === 0) {
            return b.length;
        }
        if (b.length === 0) {
            return a.length;
        }
        var row = [];
        var i;
        var j;
        for (i = 0; i <= b.length; i++) {
            row[i] = i;
        }
        for (i = 1; i <= a.length; i++) {
            var prev = i - 1;
            row[0] = i;
            for (j = 1; j <= b.length; j++) {
                var temp = row[j];
                var cost = a.charAt(i - 1) === b.charAt(j - 1) ? 0 : 1;
                row[j] = Math.min(row[j] + 1, row[j - 1] + 1, prev + cost);
                prev = temp;
            }
        }
        return row[b.length];
    }

    function fuzzyContains(haystack, needle) {
        if (haystack.indexOf(needle) !== -1) {
            return true;
        }
        if (needle.length < 4) {
            return false;
        }
        var parts = haystack.split(' ');
        for (var i = 0; i < parts.length; i++) {
            if (levenshtein(parts[i], needle) <= 1) {
                return true;
            }
        }
        return false;
    }

    function hasDomainSignal(text) {
        var words = tokenize(text);
        var expanded = expandTokens(words);
        for (var i = 0; i < expanded.length; i++) {
            if (domainWords.has(expanded[i])) {
                return true;
            }
        }
        return normalize(text).indexOf('crispy') !== -1;
    }

    function looksOffTopic(text) {
        var n = normalize(text);
        for (var i = 0; i < offTopicHints.length; i++) {
            if (n.indexOf(offTopicHints[i]) !== -1 && !hasDomainSignal(text)) {
                return true;
            }
        }
        return false;
    }

    function scoreFaq(text, faq) {
        var score = 0;
        var lower = normalize(text);
        var inputWords = expandTokens(tokenize(text));

        (faq.aliases || []).forEach(function (alias) {
            var a = normalize(alias);
            if (lower.indexOf(a) !== -1 || a.indexOf(lower) !== -1) {
                score += 8;
            }
        });

        (faq.keywords || []).forEach(function (kw) {
            var k = normalize(kw);
            if (k.indexOf(' ') !== -1) {
                if (lower.indexOf(k) !== -1) {
                    score += 6;
                }
            } else if (lower.indexOf(k) !== -1 || fuzzyContains(lower, k)) {
                score += 4;
            }
            inputWords.forEach(function (w) {
                if (w === k || levenshtein(w, k) <= 1) {
                    score += 3;
                }
            });
        });

        var qWords = tokenize(faq.question || '');
        inputWords.forEach(function (w) {
            if (qWords.indexOf(w) !== -1) {
                score += 2;
            }
        });

        if (lastFaqId && faq.id === lastFaqId && lower.length < 40) {
            if (/^(yes|ok|more|continue|and|also|what else)/.test(lower)) {
                score += 4;
            }
        }

        return score;
    }

    function rankFaqs(text) {
        var ranked = cfg.faqs
            .map(function (faq) {
                return { faq: faq, score: scoreFaq(text, faq) };
            })
            .filter(function (r) {
                return r.score > 0;
            })
            .sort(function (a, b) {
                return b.score - a.score;
            });
        return ranked;
    }

    function findAnswer(text) {
        var trimmed = text.trim();
        if (!trimmed) {
            return null;
        }

        if (/^(hi|hello|hey|help|faqs?|start)[\s!.?]*$/i.test(trimmed)) {
            return { type: 'greeting' };
        }

        if (/^(thanks|thank you|ty|salamat)[\s!.?]*$/i.test(trimmed)) {
            return { type: 'thanks' };
        }

        if (looksOffTopic(trimmed)) {
            return { type: 'off_topic' };
        }

        var ranked = rankFaqs(trimmed);
        if (ranked.length === 0) {
            return hasDomainSignal(trimmed) ? { type: 'suggest' } : { type: 'off_topic' };
        }

        var best = ranked[0];
        var second = ranked[1];

        if (best.score >= 5) {
            if (second && second.score >= best.score - 2 && second.score >= 5) {
                return { type: 'clarify', options: [best.faq, second.faq] };
            }
            return { type: 'faq', faq: best.faq };
        }

        if (best.score >= 3 && hasDomainSignal(trimmed)) {
            return { type: 'faq', faq: best.faq, weak: true };
        }

        if (best.score >= 2 && hasDomainSignal(trimmed)) {
            return { type: 'clarify', options: ranked.slice(0, 3).map(function (r) {
                return r.faq;
            }) };
        }

        return { type: 'off_topic' };
    }

    function showTyping() {
        hideTyping();
        typingEl = document.createElement('div');
        typingEl.className = 'crispy-ai__msg crispy-ai__msg--bot crispy-ai__msg--typing';
        typingEl.setAttribute('aria-hidden', 'true');
        typingEl.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(typingEl);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideTyping() {
        if (typingEl && typingEl.parentNode) {
            typingEl.parentNode.removeChild(typingEl);
        }
        typingEl = null;
    }

    function renderChips(faqs, onPick) {
        if (!chipsEl) {
            return;
        }
        chipsEl.innerHTML = '';
        var list = faqs || getDefaultChips();
        list.forEach(function (faq) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'crispy-ai__chip';
            btn.textContent = faq.question;
            btn.addEventListener('click', function () {
                if (onPick) {
                    onPick(faq);
                } else {
                    handleUserMessage(faq.question, true);
                }
            });
            chipsEl.appendChild(btn);
        });
    }

    function replyForResult(result) {
        if (result.type === 'greeting') {
            return cfg.greeting;
        }
        if (result.type === 'thanks') {
            return cfg.thanks || 'You\'re welcome!';
        }
        if (result.type === 'clarify') {
            return cfg.clarify || 'Which topic did you mean?';
        }
        if (result.type === 'suggest') {
            return 'I\'m not sure I understood. Here are topics I can help with — tap one or try rephrasing your question.';
        }
        if (result.type === 'faq') {
            return result.faq.answer;
        }
        return cfg.offTopic;
    }

    function getDefaultChips() {
        var riderFaq = null;
        var profileFaq = null;
        var rest = [];
        cfg.faqs.forEach(function (faq) {
            if (faq.id === 'rider_apply') {
                riderFaq = faq;
            } else if (faq.id === 'profile') {
                profileFaq = faq;
            } else {
                rest.push(faq);
            }
        });
        var chips = rest.slice(0, 4);
        if (profileFaq) {
            chips.push(profileFaq);
        }
        if (riderFaq) {
            chips.push(riderFaq);
        }
        return chips;
    }

    function botReply(result, callback) {
        showTyping();
        window.setTimeout(function () {
            hideTyping();
            var faq = result.type === 'faq' ? result.faq : null;
            var html = formatAnswer(replyForResult(result), faq);
            appendMessage('bot', html, true);
            if (result.type === 'clarify' && result.options) {
                renderChips(result.options, function (faq) {
                    lastFaqId = faq.id;
                    appendMessage('user', faq.question, false);
                    showTyping();
                    window.setTimeout(function () {
                        hideTyping();
                        appendMessage('bot', formatAnswer(faq.answer, faq), true);
                    }, 220);
                });
            } else if (result.type === 'suggest') {
                renderChips(getDefaultChips());
            } else if (result.type === 'faq') {
                lastFaqId = result.faq.id;
            }
            if (callback) {
                callback();
            }
        }, 380 + Math.min(400, (replyForResult(result).length * 4)));
    }

    function appendMessage(role, html, isHtml) {
        var bubble = document.createElement('div');
        bubble.className = 'crispy-ai__msg crispy-ai__msg--' + role;
        if (isHtml) {
            bubble.innerHTML = html;
        } else {
            bubble.textContent = html;
        }
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function handleUserMessage(text, skipUserBubble) {
        var trimmed = text.trim();
        if (!trimmed) {
            return;
        }

        if (!skipUserBubble) {
            appendMessage('user', trimmed, false);
        }

        var result = findAnswer(trimmed);
        botReply(result);
    }

    function openPanel() {
        panel.hidden = false;
        launcher.setAttribute('aria-expanded', 'true');
        if (!greeted) {
            greeted = true;
            appendMessage('bot', formatAnswer(cfg.greeting, null), true);
            renderChips(getDefaultChips());
        }
        window.setTimeout(function () {
            if (input) {
                input.focus();
            }
        }, 100);
    }

    function closePanel() {
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
    }

    launcher.addEventListener('click', function () {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closePanel);
    }

    if (form && input) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var text = input.value;
            input.value = '';
            handleUserMessage(text);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) {
            closePanel();
        }
    });
})();
