<?php
require_once dirname(__DIR__) . '/app/url.php';
$kkLoginUrl = app_url('login.php');
$kkRegisterUrl = app_url('register.php');
$kkForgotUrl = app_url('forgot-password.php');
?>
<div class="modal fade" id="kkAuthModal" tabindex="-1" aria-labelledby="kkAuthModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered kk-auth-dialog">
        <div class="modal-content kk-auth-modal">
            <div class="modal-header kk-auth-modal__head">
                <div class="d-flex align-items-start gap-3 flex-grow-1 min-w-0">
                    <img
                        src="<?= htmlspecialchars(app_brand_logo_url()) ?>"
                        alt=""
                        class="kk-auth-modal__logo flex-shrink-0"
                        width="48"
                        height="48"
                        decoding="async">
                    <div class="min-w-0">
                        <h2 class="modal-title h5 mb-0" id="kkAuthModalTitle">Crispy Crave</h2>
                        <p class="text-muted small mb-0 mt-1" id="kkAuthModalSubtitle">Sign in or create an account to order.</p>
                    </div>
                </div>
                <button type="button" class="kk-modal-dismiss flex-shrink-0" data-bs-dismiss="modal" aria-label="Dismiss dialog">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body pt-2">
                <div id="kkAuthAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>
                <div id="kkAuthSuccess" class="alert alert-success py-2 small d-none" role="alert"></div>

                <ul class="nav nav-pills nav-fill gap-2 mb-3 kk-auth-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active w-100" id="kk-auth-tab-login" data-bs-toggle="pill" data-bs-target="#kk-auth-pane-login" type="button" role="tab" aria-controls="kk-auth-pane-login" aria-selected="true">Log in</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link w-100" id="kk-auth-tab-register" data-bs-toggle="pill" data-bs-target="#kk-auth-pane-register" type="button" role="tab" aria-controls="kk-auth-pane-register" aria-selected="false">Sign up</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="kk-auth-pane-login" role="tabpanel" aria-labelledby="kk-auth-tab-login" tabindex="0">
                        <form id="kkFormLogin" method="post" action="<?= htmlspecialchars($kkLoginUrl) ?>" novalidate>
                            <input type="hidden" name="ajax" value="1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="kk-login-email">Email</label>
                                <input type="email" class="form-control" id="kk-login-email" name="email" required autocomplete="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="kk-login-password">Password</label>
                                <div class="input-group kk-auth-password-group">
                                    <input type="password" class="form-control" id="kk-login-password" name="password" required autocomplete="current-password">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary kk-auth-toggle-pw"
                                        aria-controls="kk-login-password"
                                        aria-label="Show password"
                                        tabindex="0">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span class="visually-hidden kk-auth-toggle-pw__sr">Show password</span>
                                    </button>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="<?= htmlspecialchars($kkForgotUrl) ?>" class="small link-secondary text-decoration-none kk-auth-forgot">Forgot password?</a>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 fw-semibold kk-auth-submit">Log in</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="kk-auth-pane-register" role="tabpanel" aria-labelledby="kk-auth-tab-register" tabindex="0">
                        <form id="kkFormRegister" method="post" action="<?= htmlspecialchars($kkRegisterUrl) ?>" novalidate>
                            <input type="hidden" name="ajax" value="1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="kk-reg-name">Full name</label>
                                <input type="text" class="form-control" id="kk-reg-name" name="name" required autocomplete="name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="kk-reg-email">Email</label>
                                <input type="email" class="form-control" id="kk-reg-email" name="email" required autocomplete="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="kk-reg-password">Password</label>
                                <div class="input-group kk-auth-password-group">
                                    <input type="password" class="form-control" id="kk-reg-password" name="password" required minlength="6" autocomplete="new-password">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary kk-auth-toggle-pw"
                                        aria-controls="kk-reg-password"
                                        aria-label="Show password"
                                        tabindex="0">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span class="visually-hidden kk-auth-toggle-pw__sr">Show password</span>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark kk-auth-submit">Create account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
