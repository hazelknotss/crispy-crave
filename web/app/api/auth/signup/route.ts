import { NextResponse } from "next/server";
import { authErrorMessage } from "@/lib/auth-errors";
import { createAdminClient } from "@/lib/supabase/admin";
import { findAuthUserIdByEmail } from "@/lib/supabase/auth-admin";

export const runtime = "nodejs";

type SignupBody = {
  email?: string;
  password?: string;
  name?: string;
};

export async function POST(request: Request) {
  let body: SignupBody;
  try {
    body = (await request.json()) as SignupBody;
  } catch {
    return NextResponse.json({ error: "Invalid request." }, { status: 400 });
  }

  const email = String(body.email ?? "")
    .trim()
    .toLowerCase();
  const password = String(body.password ?? "");
  const name = String(body.name ?? "").trim();

  if (name.length < 1) {
    return NextResponse.json({ error: "Enter your full name." }, { status: 400 });
  }
  if (!email.includes("@")) {
    return NextResponse.json({ error: "Enter a valid email." }, { status: 400 });
  }
  if (password.length < 6) {
    return NextResponse.json(
      { error: "Password must be at least 6 characters." },
      { status: 400 }
    );
  }

  const admin = createAdminClient();
  if (!admin) {
    return NextResponse.json(
      {
        error:
          "Sign-up is not configured on the server. Add SUPABASE_SERVICE_ROLE_KEY in Vercel env.",
      },
      { status: 503 }
    );
  }

  try {
    const existingId = await findAuthUserIdByEmail(email, admin);
    if (existingId) {
      return NextResponse.json(
        {
          error:
            "This email is already registered. Use the Log in tab with your password.",
          code: "already_registered",
        },
        { status: 409 }
      );
    }

    const { error: createErr } = await admin.auth.admin.createUser({
      email,
      password,
      email_confirm: true,
      user_metadata: { display_name: name },
    });

    if (createErr) {
      return NextResponse.json(
        { error: authErrorMessage(createErr) },
        { status: 400 }
      );
    }

    return NextResponse.json({
      ok: true,
      message: "Account created. Open the Log in tab and sign in with this email and password.",
    });
  } catch (e) {
    return NextResponse.json(
      { error: authErrorMessage(e) },
      { status: 500 }
    );
  }
}
