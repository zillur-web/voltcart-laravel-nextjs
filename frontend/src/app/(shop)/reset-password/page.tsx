"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { FormEvent, useState } from "react";
import { AuthShell } from "@/components/shop/AuthShell";
import { ApiError, api } from "@/lib/api";

export default function ResetPage() {
  const params = useSearchParams();
  const router = useRouter();
  const [message, setMessage] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      await api("/auth/reset-password", {
        method: "POST",
        auth: false,
        body: {
          email: params.get("email"),
          token: params.get("token"),
          password: form.get("password"),
          password_confirmation: form.get("password_confirmation"),
        },
      });
      router.push("/login");
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Reset failed.");
    }
  }

  return (
    <AuthShell title="Choose a new password" message={message}>
      <form onSubmit={submit} className="space-y-3">
        <input name="password" type="password" required placeholder="New password" className="w-full rounded-xl border border-line px-4 py-3" />
        <input name="password_confirmation" type="password" required placeholder="Confirm password" className="w-full rounded-xl border border-line px-4 py-3" />
        <button className="w-full rounded-full bg-black py-3 text-white">Update password</button>
      </form>
    </AuthShell>
  );
}
