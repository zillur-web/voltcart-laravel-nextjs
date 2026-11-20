"use client";

import { FormEvent, useState } from "react";
import { AuthShell } from "@/components/shop/AuthShell";
import { api } from "@/lib/api";

export default function ForgotPage() {
  const [message, setMessage] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const email = String(new FormData(event.currentTarget).get("email") || "");
    const response = await api("/auth/forgot-password", { method: "POST", auth: false, body: { email } });
    setMessage(response.message);
  }

  return (
    <AuthShell title="Reset password" message={message}>
      <form onSubmit={submit} className="space-y-3">
        <input name="email" type="email" required placeholder="Email" className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="w-full rounded-full bg-black py-3 text-white">Send reset link</button>
      </form>
    </AuthShell>
  );
}
