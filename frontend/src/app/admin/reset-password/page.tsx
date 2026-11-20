"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { ApiError, api } from "@/lib/api";

export default function AdminResetPage() {
  const router = useRouter();
  const [message, setMessage] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const params = new URLSearchParams(window.location.search);
    try {
      await api("/admin/auth/reset-password", {
        method: "POST",
        auth: false,
        body: {
          email: params.get("email"),
          token: params.get("token"),
          password: form.get("password"),
          password_confirmation: form.get("password_confirmation"),
        },
      });
      router.push("/admin/login");
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Reset failed.");
    }
  }

  return (
    <main className="mx-auto max-w-md px-4 py-20">
      <h1 className="text-2xl font-bold">New staff password</h1>
      <form onSubmit={submit} className="mt-6 space-y-3">
        <input name="password" type="password" required className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="password_confirmation" type="password" required className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="rounded-full bg-black px-5 py-3 text-white">Update</button>
      </form>
      {message ? <p className="mt-3 text-sm text-sale">{message}</p> : null}
    </main>
  );
}
