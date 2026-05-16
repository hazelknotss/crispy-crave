import { NextResponse } from "next/server";
import { createAdminClient } from "@/lib/supabase/admin";

const MAX_BYTES = 5 * 1024 * 1024;
const VEHICLE_TYPES = new Set(["motorcycle", "bicycle", "car"]);

function safeExt(original: string): string {
  const raw = (original.split(".").pop() ?? "").toLowerCase().replace(/[^a-z0-9]/g, "");
  if (raw === "jpeg") return "jpg";
  if (["jpg", "png", "webp", "gif", "pdf"].includes(raw)) return raw;
  return "bin";
}

export async function POST(request: Request) {
  const admin = createAdminClient();
  if (!admin) {
    return NextResponse.json(
      {
        error:
          "Server is missing SUPABASE_SERVICE_ROLE_KEY. Add it to .env.local to enable rider applications with document upload.",
      },
      { status: 503 }
    );
  }

  const ct = request.headers.get("content-type") ?? "";
  if (!ct.includes("multipart/form-data")) {
    return NextResponse.json({ error: "Expected multipart form data." }, { status: 400 });
  }

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return NextResponse.json({ error: "Invalid form payload." }, { status: 400 });
  }

  const name = String(form.get("name") ?? "").trim();
  const email = String(form.get("email") ?? "").trim().toLowerCase();
  const password = String(form.get("password") ?? "");
  const phone = String(form.get("phone") ?? "").trim();
  let vehicleType = String(form.get("vehicle_type") ?? "motorcycle").trim();
  const vehiclePlate = String(form.get("vehicle_plate") ?? "").trim() || null;

  const license = form.get("doc_license");
  const idDoc = form.get("doc_id");

  if (name.length < 2 || email.length < 3 || phone.length < 5) {
    return NextResponse.json({ error: "Please complete all required fields." }, { status: 400 });
  }
  if (password.length < 6) {
    return NextResponse.json({ error: "Password must be at least 6 characters." }, { status: 400 });
  }
  if (!VEHICLE_TYPES.has(vehicleType)) {
    vehicleType = "motorcycle";
  }

  if (!(license instanceof File) || license.size === 0) {
    return NextResponse.json({ error: "Please upload your driver's license." }, { status: 400 });
  }
  if (!(idDoc instanceof File) || idDoc.size === 0) {
    return NextResponse.json({ error: "Please upload a valid ID." }, { status: 400 });
  }
  if (license.size > MAX_BYTES || idDoc.size > MAX_BYTES) {
    return NextResponse.json({ error: "Each file must be 5MB or smaller." }, { status: 400 });
  }

  const { data: created, error: createErr } = await admin.auth.admin.createUser({
    email,
    password,
    email_confirm: true,
    user_metadata: {
      display_name: name,
      signup_role: "rider",
    },
  });

  if (createErr || !created.user) {
    const msg = createErr?.message ?? "Could not create account.";
    const dup =
      /already|registered|exists/i.test(msg) || createErr?.status === 422;
    return NextResponse.json(
      { error: dup ? "That email is already registered." : msg },
      { status: dup ? 400 : 500 }
    );
  }

  const userId = created.user.id;
  const ts = Date.now();
  const licenseKey = `${userId}/license_${ts}.${safeExt(license.name)}`;
  const idKey = `${userId}/id_photo_${ts}.${safeExt(idDoc.name)}`;

  try {
    const licBuf = Buffer.from(await license.arrayBuffer());
    const idBuf = Buffer.from(await idDoc.arrayBuffer());

    const { error: up1 } = await admin.storage.from("rider-documents").upload(licenseKey, licBuf, {
      contentType: license.type || "application/octet-stream",
      upsert: false,
    });
    if (up1) throw up1;

    const { error: up2 } = await admin.storage.from("rider-documents").upload(idKey, idBuf, {
      contentType: idDoc.type || "application/octet-stream",
      upsert: false,
    });
    if (up2) throw up2;

    const { error: rpErr } = await admin.from("rider_profiles").insert({
      user_id: userId,
      phone,
      vehicle_type: vehicleType,
      vehicle_plate: vehiclePlate,
      fleet_status: "available",
      updated_at: new Date().toISOString(),
    });
    if (rpErr) throw rpErr;

    const { error: d1 } = await admin.from("rider_documents").insert({
      user_id: userId,
      doc_type: "license",
      storage_path: licenseKey,
      status: "pending",
    });
    if (d1) throw d1;

    const { error: d2 } = await admin.from("rider_documents").insert({
      user_id: userId,
      doc_type: "id_photo",
      storage_path: idKey,
      status: "pending",
    });
    if (d2) throw d2;
  } catch (e) {
    await admin.auth.admin.deleteUser(userId);
    console.error("rider apply rollback:", e);
    return NextResponse.json(
      { error: "Could not submit application. Please try again." },
      { status: 500 }
    );
  }

  return NextResponse.json({
    ok: true,
    message:
      "Application submitted! You can sign in once an admin approves your account.",
  });
}
