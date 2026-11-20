"use client";

import { FormEvent, useState } from "react";
import { api } from "@/lib/api";

export default function AdminForgotPage() {
  const [message, setMessage] = useState("");
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const response = await api("/admin/auth/forgot-password", {
      method: "POST",
      auth: false,
      body: { email: new FormData(event.currentTarget).get("email") },
    });
    setMessage(response.message);
  }
  return (
    <main className="mx-auto max-w-md px-4 py-20">
      <h1 className="text-2xl font-bold">Reset staff password</h1>
      <form onSubmit={submit} className="mt-6 space-y-3">
        <input name="email" type="email" required className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="rounded-full bg-black px-5 py-3 text-white">Send link</button>
      </form>
      {message ? <p className="mt-3 text-sm">{message}</p> : null}
    </main>
  );
}
