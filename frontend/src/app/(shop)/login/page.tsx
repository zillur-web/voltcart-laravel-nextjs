"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { FormEvent, useState } from "react";
import { AuthShell } from "@/components/shop/AuthShell";
import { ApiError, api } from "@/lib/api";

export default function LoginPage() {
  const router = useRouter();
  const params = useSearchParams();
  const [message, setMessage] = useState(params.get("verified") ? "Email verified. You can sign in." : "");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      const response = await api<{ token: string; user: { name: string } }>("/auth/login", {
        method: "POST",
        auth: false,
        body: { email: form.get("email"), password: form.get("password") },
      });
      localStorage.setItem("shopco_token", response.data.token);
      localStorage.setItem("shopco_user", JSON.stringify(response.data.user));
      await api("/cart/merge", { method: "POST" }).catch(() => undefined);
      router.push(params.get("next") || "/");
      router.refresh();
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not sign in.");
    }
  }

  return (
    <AuthShell title="Welcome back" message={message}>
      <form onSubmit={submit} className="space-y-3">
        <input name="email" type="email" required placeholder="Email" className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="password" type="password" required placeholder="Password" className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="w-full rounded-full bg-black py-3 text-white">Sign in</button>
      </form>
      <p className="mt-4 text-sm">
        <Link href="/forgot-password" className="underline">Forgot password</Link>
      </p>
      <p className="mt-2 text-sm text-black/60">
        New here? <Link href="/register" className="underline">Create an account</Link>
      </p>
    </AuthShell>
  );
}
