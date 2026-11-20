"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { StaffUser } from "@/lib/types";

export default function AdminLoginPage() {
  const router = useRouter();
  const [message, setMessage] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      const response = await api<{ token: string; user: StaffUser }>("/admin/auth/login", {
        method: "POST",
        auth: false,
        body: { email: form.get("email"), password: form.get("password") },
      });
      localStorage.setItem("shopco_admin_token", response.data.token);
      router.push("/admin");
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not sign in.");
    }
  }

  return (
    <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4">
      <p className="font-display text-3xl">SHOP.CO</p>
      <h1 className="mt-4 text-2xl font-bold">Staff sign in</h1>
      <form onSubmit={submit} className="mt-6 space-y-3">
        <input name="email" type="email" required placeholder="Work email" className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="password" type="password" required placeholder="Password" className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="w-full rounded-full bg-black py-3 text-white">Sign in</button>
      </form>
      {message ? <p className="mt-3 text-sm text-sale">{message}</p> : null}
      <Link href="/admin/forgot-password" className="mt-4 text-sm underline">
        Forgot password
      </Link>
    </main>
  );
}
