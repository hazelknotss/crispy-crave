/** Coerce API/Supabase errors to a safe string (never show "{}" or [object Object]). */
export function ensureAuthErrorText(value: unknown): string {
  if (typeof value === "string") {
    const t = value.trim();
    if (t.length > 0 && t !== "{}") return t;
  }
  if (value && typeof value === "object") {
    return authErrorMessage(value);
  }
  return "Something went wrong. Please try again.";
}

/** Human-readable text from Supabase Auth errors (avoids showing "{}" in the UI). */
export function authErrorMessage(error: unknown): string {
  if (!error || typeof error !== "object") {
    return "Something went wrong. Please try again.";
  }

  const e = error as { message?: string; code?: string; status?: number };
  const msg = typeof e.message === "string" ? e.message.trim() : "";
  if (msg.length > 0 && msg !== "{}") {
    return msg;
  }

  switch (e.code) {
    case "user_already_exists":
    case "email_exists":
      return "This email is already registered. Use the Log in tab, or delete the old account in Supabase → Authentication → Users.";
    case "over_email_send_rate_limit":
      return "Too many emails sent. Wait a few minutes, then try Resend confirmation.";
    case "signup_disabled":
      return "Sign-up is disabled right now. Try again later.";
    case "weak_password":
      return "Password is too weak. Use at least 6 characters.";
    case "email_address_invalid":
      return "That email address is not allowed. Try another email.";
    default:
      if (e.status === 422) {
        return "Check your email and password, then try again.";
      }
      if (e.status === 429) {
        return "Too many attempts. Please wait a minute and try again.";
      }
      return "Something went wrong. Please try again.";
  }
}
