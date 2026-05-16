'use client';

import { useEffect, useState } from 'react';
import { createClient } from '@/lib/supabase/client';
import { authErrorMessage } from '@/lib/auth-errors';
import { BRAND_LOGO_SRC } from '@/lib/brand';

function authCallbackUrl(next = '/'): string {
  return `${window.location.origin}/auth/callback?next=${encodeURIComponent(next)}`;
}

export function AuthModalOpener() {
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const needOpen =
      params.get('login') === 'required' ||
      params.get('auth') === 'login' ||
      params.get('auth') === 'error';
    if (!needOpen) return;

    let attempts = 0;
    const id = window.setInterval(() => {
      attempts += 1;
      const el = document.getElementById('kkAuthModal');
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const B = (window as unknown as { bootstrap?: any }).bootstrap;
      if (el && B?.Modal) {
        B.Modal.getOrCreateInstance(el).show();
        window.clearInterval(id);
        params.delete('login');
        params.delete('auth');
        params.delete('message');
        const qs = params.toString();
        const next = `${window.location.pathname}${qs ? `?${qs}` : ''}${window.location.hash || ''}`;
        window.history.replaceState({}, '', next);
      } else if (attempts > 40) {
        window.clearInterval(id);
      }
    }, 100);

    return () => window.clearInterval(id);
  }, []);

  return null;
}

export function AuthModal() {
  const [err, setErr] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [pendingConfirmEmail, setPendingConfirmEmail] = useState<string | null>(null);
  const [showLoginPassword, setShowLoginPassword] = useState(false);
  const [showRegisterPassword, setShowRegisterPassword] = useState(false);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('auth') === 'error') {
      const msg = params.get('message');
      setErr(msg ? decodeURIComponent(msg) : 'Sign-in link expired or invalid. Try again.');
    }
  }, []);

  useEffect(() => {
    const el = document.getElementById('kkAuthModal');
    if (!el) return;
    const onHidden = () => {
      setShowLoginPassword(false);
      setShowRegisterPassword(false);
    };
    el.addEventListener('hidden.bs.modal', onHidden);
    return () => el.removeEventListener('hidden.bs.modal', onHidden);
  }, []);

  async function onLogin(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setErr(null);
    setOk(null);
    setLoading(true);
    const fd = new FormData(e.currentTarget);
    const email = String(fd.get('email') ?? '');
    const password = String(fd.get('password') ?? '');
    const supabase = createClient();
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    setLoading(false);
    if (error) {
      const msg = error.message.toLowerCase();
      if (msg.includes('email not confirmed') || msg.includes('not verified')) {
        setPendingConfirmEmail(email.trim().toLowerCase());
        setErr('Confirm your email first (check inbox and spam), or resend the link below.');
      } else {
        setErr(authErrorMessage(error));
      }
      return;
    }
    setPendingConfirmEmail(null);
    window.location.href = '/';
  }

  function switchToLoginTab(email?: string) {
    document.getElementById('kk-auth-tab-login')?.click();
    if (email) {
      const input = document.getElementById('kk-login-email') as HTMLInputElement | null;
      if (input) input.value = email;
    }
  }

  async function resendConfirmation() {
    if (!pendingConfirmEmail) return;
    setErr(null);
    setOk(null);
    setLoading(true);
    const supabase = createClient();
    const { error } = await supabase.auth.resend({
      type: 'signup',
      email: pendingConfirmEmail,
      options: { emailRedirectTo: authCallbackUrl('/') },
    });
    setLoading(false);
    if (error) {
      setErr(authErrorMessage(error));
      return;
    }
    setOk(`Confirmation email sent again to ${pendingConfirmEmail}. Check spam/junk too.`);
  }

  async function onRegister(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setErr(null);
    setOk(null);
    setLoading(true);
    const fd = new FormData(e.currentTarget);
    const email = String(fd.get('email') ?? '').trim().toLowerCase();
    const password = String(fd.get('password') ?? '');
    const name = String(fd.get('name') ?? '');
    const supabase = createClient();
    const { data, error } = await supabase.auth.signUp({
      email,
      password,
      options: {
        data: { display_name: name },
        emailRedirectTo: authCallbackUrl('/'),
      },
    });
    setLoading(false);
    if (error) {
      setErr(authErrorMessage(error));
      return;
    }
    if (data.user?.identities?.length === 0) {
      setPendingConfirmEmail(email);
      setErr(
        'This email is already registered. Use the Log in tab with your password, or tap below to resend confirmation if you never verified.'
      );
      switchToLoginTab(email);
      return;
    }
    if (data.session) {
      window.location.href = '/';
      return;
    }
    setPendingConfirmEmail(email);
    setOk(
      `We sent a confirmation link to ${email}. Open it to activate your account (check spam/junk). Then sign in.`
    );
  }

  return (
    <div
      className="modal fade"
      id="kkAuthModal"
      tabIndex={-1}
      aria-labelledby="kkAuthModalTitle"
      aria-hidden="true"
    >
      <div className="modal-dialog modal-dialog-centered kk-auth-dialog">
        <div className="modal-content kk-auth-modal">
          <div className="modal-header kk-auth-modal__head">
            <div className="d-flex align-items-start gap-3 flex-grow-1 min-w-0">
              <img
                src={BRAND_LOGO_SRC}
                alt=""
                className="kk-auth-modal__logo flex-shrink-0"
                width={48}
                height={48}
                decoding="async"
              />
              <div className="min-w-0">
                <h2 className="modal-title h5 mb-0" id="kkAuthModalTitle">
                  Crispy Crave
                </h2>
                <p className="text-muted small mb-0 mt-1" id="kkAuthModalSubtitle">
                  Sign in or create an account to order.
                </p>
              </div>
            </div>
            <button
              type="button"
              className="kk-modal-dismiss flex-shrink-0"
              data-bs-dismiss="modal"
              aria-label="Dismiss dialog"
            >
              <i className="bi bi-x-lg" aria-hidden="true" />
            </button>
          </div>
          <div className="modal-body pt-2">
            {err && err.trim() !== "{}" ? (
              <div className="alert alert-danger py-2 small" role="alert">
                {err}
              </motion.div>
            ) : null}
            {pendingConfirmEmail ? (
              <p className="small mb-3">
                <button
                  type="button"
                  className="btn btn-link btn-sm p-0 align-baseline"
                  disabled={loading}
                  onClick={() => void resendConfirmation()}
                >
                  Resend confirmation email
                </button>
              </p>
            ) : null}
            {ok ? (
              <div className="alert alert-success py-2 small" role="status">
                {ok}
              </div>
            ) : null}

            <ul
              className="nav nav-pills nav-fill gap-2 mb-3 kk-auth-tabs"
              role="tablist"
            >
              <li className="nav-item" role="presentation">
                <button
                  className="nav-link active w-100"
                  id="kk-auth-tab-login"
                  data-bs-toggle="pill"
                  data-bs-target="#kk-auth-pane-login"
                  type="button"
                  role="tab"
                  aria-controls="kk-auth-pane-login"
                  aria-selected="true"
                >
                  Log in
                </button>
              </li>
              <li className="nav-item" role="presentation">
                <button
                  className="nav-link w-100"
                  id="kk-auth-tab-register"
                  data-bs-toggle="pill"
                  data-bs-target="#kk-auth-pane-register"
                  type="button"
                  role="tab"
                  aria-controls="kk-auth-pane-register"
                  aria-selected="false"
                >
                  Sign up
                </button>
              </li>
            </ul>

            <div className="tab-content">
              <div
                className="tab-pane fade show active"
                id="kk-auth-pane-login"
                role="tabpanel"
                aria-labelledby="kk-auth-tab-login"
                tabIndex={0}
              >
                <form id="kkFormLogin" onSubmit={onLogin} noValidate>
                  <div className="mb-3">
                    <label className="form-label fw-semibold" htmlFor="kk-login-email">
                      Email
                    </label>
                    <input
                      type="email"
                      className="form-control"
                      id="kk-login-email"
                      name="email"
                      required
                      autoComplete="email"
                    />
                  </div>
                  <div className="mb-3">
                    <label className="form-label fw-semibold" htmlFor="kk-login-password">
                      Password
                    </label>
                    <div className="input-group kk-auth-password-group">
                      <input
                        type={showLoginPassword ? 'text' : 'password'}
                        className="form-control"
                        id="kk-login-password"
                        name="password"
                        required
                        autoComplete="current-password"
                      />
                      <button
                        type="button"
                        className="btn btn-outline-secondary kk-auth-toggle-pw"
                        aria-controls="kk-login-password"
                        aria-label={showLoginPassword ? 'Hide password' : 'Show password'}
                        onClick={() => setShowLoginPassword((v) => !v)}
                      >
                        <i
                          className={`bi ${showLoginPassword ? 'bi-eye-slash' : 'bi-eye'}`}
                          aria-hidden="true"
                        />
                        <span className="visually-hidden kk-auth-toggle-pw__sr">
                          {showLoginPassword ? 'Hide password' : 'Show password'}
                        </span>
                      </button>
                    </div>
                  </div>
                  <button
                    type="submit"
                    className="btn btn-dark w-100 fw-semibold"
                    disabled={loading}
                  >
                    Log in
                  </button>
                </form>
              </div>
              <div
                className="tab-pane fade"
                id="kk-auth-pane-register"
                role="tabpanel"
                aria-labelledby="kk-auth-tab-register"
                tabIndex={0}
              >
                <form id="kkFormRegister" onSubmit={onRegister} noValidate>
                  <div className="mb-3">
                    <label className="form-label fw-semibold" htmlFor="kk-reg-name">
                      Full name
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      id="kk-reg-name"
                      name="name"
                      required
                      autoComplete="name"
                    />
                  </div>
                  <div className="mb-3">
                    <label className="form-label fw-semibold" htmlFor="kk-reg-email">
                      Email
                    </label>
                    <input
                      type="email"
                      className="form-control"
                      id="kk-reg-email"
                      name="email"
                      required
                      autoComplete="email"
                    />
                  </div>
                  <div className="mb-3">
                    <label className="form-label fw-semibold" htmlFor="kk-reg-password">
                      Password
                    </label>
                    <div className="input-group kk-auth-password-group">
                      <input
                        type={showRegisterPassword ? 'text' : 'password'}
                        className="form-control"
                        id="kk-reg-password"
                        name="password"
                        required
                        minLength={6}
                        autoComplete="new-password"
                      />
                      <button
                        type="button"
                        className="btn btn-outline-secondary kk-auth-toggle-pw"
                        aria-controls="kk-reg-password"
                        aria-label={showRegisterPassword ? 'Hide password' : 'Show password'}
                        onClick={() => setShowRegisterPassword((v) => !v)}
                      >
                        <i
                          className={`bi ${showRegisterPassword ? 'bi-eye-slash' : 'bi-eye'}`}
                          aria-hidden="true"
                        />
                        <span className="visually-hidden kk-auth-toggle-pw__sr">
                          {showRegisterPassword ? 'Hide password' : 'Show password'}
                        </span>
                      </button>
                    </div>
                  </div>
                  <button
                    type="submit"
                    className="btn btn-warning w-100 fw-semibold text-dark"
                    disabled={loading}
                  >
                    Create account
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
