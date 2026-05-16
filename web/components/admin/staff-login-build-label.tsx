/** Bumped when staff login behavior changes — visible proof the new bundle loaded. */
export const STAFF_LOGIN_BUILD = "2026-05-16-v3";

export function StaffLoginBuildLabel() {
  const sha = process.env.VERCEL_GIT_COMMIT_SHA?.slice(0, 7);
  return (
    <p className="text-muted small text-center mt-3 mb-0" data-staff-login-build={STAFF_LOGIN_BUILD}>
      Staff login {STAFF_LOGIN_BUILD}
      {sha ? ` · deploy ${sha}` : null}
    </p>
  );
}
