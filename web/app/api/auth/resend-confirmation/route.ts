import { NextResponse } from "next/server";
import { authErrorMessage } from "@/lib/auth-errors";
import { createAdminClient } from "@/lib/supabase/admin";
import { findAuthUserIdByEmail } from "@/lib/supabase/auth-admin";
import { getSupabasePublicConfig } from "@/lib/supabase/env";

export const runtime = "nodejs";

export async function POST(request: Request) {
  let email = "";
  try {
    const body = (await request.json()) as { email?: string };
    email = String(body.email ?? "")
      .trim()
      .toLowerCase();
  } catch {
    return NextResponse.json({ error: "Invalid request." }, { status: 400 });
  }

  if (!email.includes("@")) {
    return NextResponse.json({ error: "Enter a valid email." }, { status: 400 });
  }

  const admin = createAdminClient();
  if (!admin) {
    return NextResponse.json(
      { error: "Server email is not configured (SUPABASE_SERVICE_ROLE_KEY)." },
      { status: 503 }
    );
  }

  const userId = await findAuthUserIdByEmail(email, admin);
  if (!userId) {
    return NextResponse.json(
      { error: "No account found for that email. Sign up first." },
      { status: 404 }
    );
  }

  const origin = new URL(request.url).origin;
  const redirectTo = `${origin}/auth/callback?next=/`;
  const cfg = getSupabasePublicConfig();

  const { error } = await admin.auth.admin.inviteUserByEmail(email, {
    redirectTo,
  });

  if (error) {
    return NextResponse.json({ error: authErrorMessage(error) }, { status: 400 });
  }

  return NextResponse.json({
    ok: true,
    message: cfg
      ? `If email delivery is enabled, a link was sent to ${email}. Check spam. You can also sign in if your account is already confirmed in Supabase.`
      : `Confirmation link generated for ${email}.`,
  });
}
