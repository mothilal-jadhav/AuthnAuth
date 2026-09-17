function initToasts() {
    var region = document.getElementById('toast-region');
    var dataEl = document.getElementById('toast-flash-data');

    if (!region) {
        return;
    }

    var styles = {
        success: { bg: 'bg-success-bg', text: 'text-success', border: 'border-success-line', icon: '✓' },
        error: { bg: 'bg-danger-bg', text: 'text-danger', border: 'border-danger-line', icon: '!' },
        warning: { bg: 'bg-warning-bg', text: 'text-warning', border: 'border-warning-line', icon: '!' },
        info: { bg: 'bg-info-bg', text: 'text-info', border: 'border-info-line', icon: 'i' },
    };

    function showToast(type, message) {
        var style = styles[type] || styles.info;

        var toast = document.createElement('div');
        toast.setAttribute('role', 'status');
        toast.className = 'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-md border px-4 py-3 text-sm shadow-md transition duration-200 ease-out '
            + 'translate-y-[-8px] opacity-0 '
            + style.bg + ' ' + style.border + ' ' + style.text;

        toast.innerHTML =
            '<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ' + style.border + ' text-xs font-bold">' + style.icon + '</span>'
            + '<div class="flex-1"></div>'
            + '<button type="button" class="text-current/60 transition hover:text-current" aria-label="Dismiss">&times;</button>';

        toast.querySelector('.flex-1').textContent = message;

        function dismiss() {
            toast.classList.add('opacity-0');
            setTimeout(function () {
                toast.remove();
            }, 200);
        }

        toast.querySelector('button').addEventListener('click', dismiss);
        region.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.remove('translate-y-[-8px]', 'opacity-0');
        });

        setTimeout(dismiss, 5000);
    }

    if (dataEl) {
        try {
            var flashes = JSON.parse(dataEl.textContent);
            flashes.forEach(function (flash) {
                showToast(flash.type, flash.message);
            });
        } catch (e) {
            // malformed flash payload, nothing to show
        }
        dataEl.remove();
    }
}

function initConfirmDialog() {
    var dialog = document.getElementById('confirm-dialog');

    if (!dialog) {
        return;
    }

    var messageEl = document.getElementById('confirm-dialog-message');
    var acceptBtn = dialog.querySelector('[data-confirm-accept]');
    var cancelBtn = dialog.querySelector('[data-confirm-cancel]');
    var pendingForm = null;

    function open(message, form) {
        messageEl.textContent = message;
        pendingForm = form;
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        acceptBtn.focus();
    }

    function close() {
        dialog.classList.remove('flex');
        dialog.classList.add('hidden');
        pendingForm = null;
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        var message = form.dataset ? form.dataset.confirm : null;

        if (!message || form.dataset.confirmed === 'true') {
            return;
        }

        e.preventDefault();
        open(message, form);
    });

    acceptBtn.addEventListener('click', function () {
        if (!pendingForm) {
            return;
        }

        var form = pendingForm;
        form.dataset.confirmed = 'true';
        close();
        form.requestSubmit ? form.requestSubmit() : form.submit();
    });

    cancelBtn.addEventListener('click', close);

    dialog.addEventListener('click', function (e) {
        if (e.target === dialog) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
            close();
        }
    });
}

function initToggles() {
    document.querySelectorAll('[data-show]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var hideEl = document.getElementById(btn.dataset.hide);
            var showEl = document.getElementById(btn.dataset.show);

            if (hideEl) {
                hideEl.classList.add('hidden');
            }

            if (showEl) {
                showEl.classList.remove('hidden');
            }
        });
    });
}

function initProfileTabs() {
    var buttons = document.querySelectorAll('[data-tab-button]');

    if (!buttons.length) {
        return;
    }

    var panels = document.querySelectorAll('[data-tab-panel]');

    function activate(name) {
        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== name);
        });

        buttons.forEach(function (btn) {
            var active = btn.dataset.tabButton === name;
            btn.classList.toggle('bg-paper-alt', active);
            btn.classList.toggle('text-ink', active);
            btn.classList.toggle('text-ink-muted', !active);
        });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activate(btn.dataset.tabButton);
        });
    });
}

function initPasswordVerify() {
    var continueBtn = document.getElementById('password-verify-continue');

    if (!continueBtn) {
        return;
    }

    var currentInput = document.getElementById('current_password');
    var errorEl = document.getElementById('password-verify-error');
    var step2 = document.getElementById('password-step-2');
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

    continueBtn.addEventListener('click', function () {
        errorEl.classList.add('hidden');
        continueBtn.disabled = true;

        var originalText = continueBtn.textContent;
        continueBtn.textContent = 'Checking…';

        fetch(continueBtn.dataset.verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ current_password: currentInput.value }),
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { ok: res.ok, body: body };
                });
            })
            .then(function (result) {
                if (result.ok) {
                    step2.classList.remove('hidden');
                    step2.classList.add('flex');
                    continueBtn.textContent = 'Verified';

                    return;
                }

                var fieldErrors = result.body.errors && result.body.errors.current_password;
                errorEl.textContent = (fieldErrors && fieldErrors[0]) || result.body.message || 'Something went wrong. Please try again.';
                errorEl.classList.remove('hidden');
                continueBtn.disabled = false;
                continueBtn.textContent = originalText;
            })
            .catch(function () {
                errorEl.textContent = 'Something went wrong. Please try again.';
                errorEl.classList.remove('hidden');
                continueBtn.disabled = false;
                continueBtn.textContent = originalText;
            });
    });
}

function initSubmitLoadingState() {
    document.addEventListener('submit', function (e) {
        var form = e.target;

        if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
            return;
        }

        var submitButton = form.querySelector('button[type="submit"]');

        if (!submitButton || submitButton.disabled) {
            return;
        }

        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.textContent = 'Please wait…';
    });
}

function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit]').forEach(function (field) {
        field.addEventListener('change', function () {
            field.form.submit();
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initToasts();
    initConfirmDialog();
    initSubmitLoadingState();
    initToggles();
    initProfileTabs();
    initPasswordVerify();
    initAutoSubmit();
});
