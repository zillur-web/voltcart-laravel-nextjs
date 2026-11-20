"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { AuthShell } from "@/components/shop/AuthShell";
import { ApiError, api, fieldError } from "@/lib/api";

export default function RegisterPage() {
  const router = useRouter();
  const [message, setMessage] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const password = String(form.get("password") || "");
    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/.test(password)) {
      setMessage("Use 8+ characters with upper, lower, a number, and a symbol.");
      return;
    }
    try {
      const response = await api<{ token: string; user: { name: string } }>("/auth/register", {
        method: "POST",
        auth: false,
        body: {
          name: form.get("name"),
          email: form.get("email"),
          password,
          password_confirmation: form.get("password_confirmation"),
        },
      });
      localStorage.setItem("shopco_token", response.data.token);
      localStorage.setItem("shopco_user", JSON.stringify(response.data.user));
      await api("/cart/merge", { method: "POST" }).catch(() => undefined);
      router.push("/");
    } catch (error) {
      setMessage(error instanceof ApiError ? fieldError(error, "email") || error.message : "Could not register.");
    }
  }

  return (
    <AuthShell title="Create account" message={message}>
      <form onSubmit={submit} className="space-y-3">
        <input name="name" required placeholder="Name" className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="email" type="email" required placeholder="Email" className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="password" type="password" required placeholder="Password" className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="password_confirmation" type="password" required placeholder="Confirm password" className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="w-full rounded-full bg-black py-3 text-white">Create account</button>
      </form>
      <p className="mt-4 text-sm text-black/60">
        Already registered? <Link href="/login" className="underline">Sign in</Link>
      </p>
    </AuthShell>
  );
}
