"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import {
  cardBrandFromNumber,
  getCustomerProfile,
} from "@/lib/customer-profile";

export async function profileUpdateAccount(formData: FormData) {
  const name = String(formData.get("name") ?? "").trim();
  const phone = String(formData.get("phone") ?? "").trim();
  if (name === "") {
    redirect("/profile?error=" + encodeURIComponent("Name cannot be empty."));
  }

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/?login=required");

  const { error: e1 } = await supabase
    .from("profiles")
    .update({ display_name: name, updated_at: new Date().toISOString() })
    .eq("id", user.id);

  if (e1) {
    redirect("/profile?error=" + encodeURIComponent(e1.message));
  }

  const row = await getCustomerProfile(supabase, user.id);
  const { error: e2 } = await supabase.from("customer_profiles").upsert(
    {
      ...row,
      user_id: user.id,
      phone: phone || null,
      updated_at: new Date().toISOString(),
    },
    { onConflict: "user_id" }
  );

  if (e2) {
    redirect("/profile?error=" + encodeURIComponent(e2.message));
  }

  revalidatePath("/profile");
  redirect("/profile?saved=account");
}

export async function profileUpdatePayments(formData: FormData) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) redirect("/?login=required");

  const existing = await getCustomerProfile(supabase, user.id);

  const gcashNumber = String(formData.get("gcash_number") ?? "").trim();
  const gcashName = String(formData.get("gcash_account_name") ?? "").trim();
  const bankName = String(formData.get("bank_name") ?? "").trim();
  const bankAccName = String(formData.get("bank_account_name") ?? "").trim();
  let bankAccNumber = String(formData.get("bank_account_number") ?? "").trim();
  if (bankAccNumber === "") {
    bankAccNumber = existing.bank_account_number ?? "";
  }

  const cardHolder = String(formData.get("card_holder_name") ?? "").trim();
  const cardDigits = String(formData.get("card_number") ?? "").replace(/\D/g, "");
  let cardLast4 = existing.card_last4;
  let cardBrand = existing.card_brand;
  if (cardDigits !== "") {
    cardLast4 = cardDigits.slice(-4);
    cardBrand = cardBrandFromNumber(cardDigits);
  }

  let expMonth = existing.card_exp_month;
  let expYear = existing.card_exp_year;
  const em = String(formData.get("card_exp_month") ?? "").trim();
  const ey = String(formData.get("card_exp_year") ?? "").trim();
  if (em !== "") expMonth = parseInt(em, 10);
  if (ey !== "") expYear = parseInt(ey, 10);

  let preferredRaw = String(formData.get("preferred_payment") ?? "").trim();
  if (!["", "cod", "gcash", "bank", "card"].includes(preferredRaw)) {
    preferredRaw = existing.preferred_payment ?? "";
  }
  const preferredPayment: string | null = preferredRaw === "" ? null : preferredRaw;

  const { error } = await supabase.from("customer_profiles").upsert(
    {
      user_id: user.id,
      phone: existing.phone,
      gcash_number: gcashNumber || null,
      gcash_account_name: gcashName || null,
      bank_name: bankName || null,
      bank_account_name: bankAccName || null,
      bank_account_number: bankAccNumber || null,
      card_holder_name: cardHolder || null,
      card_last4: cardLast4 || null,
      card_brand: cardBrand || null,
      card_exp_month: expMonth,
      card_exp_year: expYear,
      preferred_payment: preferredPayment,
      updated_at: new Date().toISOString(),
    },
    { onConflict: "user_id" }
  );

  if (error) {
    redirect("/profile?error=" + encodeURIComponent(error.message));
  }

  revalidatePath("/profile");
  redirect("/profile?saved=payments");
}

export async function profileUpdatePassword(formData: FormData) {
  const current = String(formData.get("current_password") ?? "");
  const newPass = String(formData.get("new_password") ?? "");
  const confirm = String(formData.get("confirm_password") ?? "");

  if (newPass.length < 6) {
    redirect("/profile?error=" + encodeURIComponent("New password must be at least 6 characters."));
  }
  if (newPass !== confirm) {
    redirect("/profile?error=" + encodeURIComponent("New passwords do not match."));
  }

  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user?.email) redirect("/?login=required");

  const { error: signErr } = await supabase.auth.signInWithPassword({
    email: user.email,
    password: current,
  });
  if (signErr) {
    redirect("/profile?error=" + encodeURIComponent("Current password is incorrect."));
  }

  const { error: upErr } = await supabase.auth.updateUser({ password: newPass });
  if (upErr) {
    redirect("/profile?error=" + encodeURIComponent(upErr.message));
  }

  revalidatePath("/profile");
  redirect("/profile?saved=password");
}
