"use client";

import { FormEvent, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { StaffUser } from "@/lib/types";

export default function ProfilePage() {
  const [user, setUser] = useState<StaffUser | null>(null);
  const [message, setMessage] = useState("");

  useEffect(() => {
    api<StaffUser>("/admin/auth/me", { auth: "admin" }).then((response) => setUser(response.data));
  }, []);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const password = String(form.get("password") || "");
    try {
      const response = await api<StaffUser>("/admin/profile", {
        method: "PUT",
        auth: "admin",
        body: {
          name: form.get("name"),
          current_password: form.get("current_password") || undefined,
          password: password || undefined,
          password_confirmation: form.get("password_confirmation") || undefined,
        },
      });
      setUser(response.data);
      setMessage("Profile updated.");
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not update profile.");
    }
  }

  if (!user) return <p className="text-sm">Loading profile…</p>;

  return (
    <form onSubmit={submit} className="max-w-md space-y-3">
      <h1 className="text-2xl font-bold">Profile</h1>
      <p className="text-sm text-black/50">{user.roles.map((role) => role.name).join(", ")}</p>
      <input name="name" defaultValue={user.name} required className="w-full rounded-lg border border-black/10 bg-white px-3 py-2" />
      <input name="current_password" type="password" placeholder="Current password" className="w-full rounded-lg border border-black/10 bg-white px-3 py-2" />
      <input name="password" type="password" placeholder="New password" className="w-full rounded-lg border border-black/10 bg-white px-3 py-2" />
      <input name="password_confirmation" type="password" placeholder="Confirm new password" className="w-full rounded-lg border border-black/10 bg-white px-3 py-2" />
      <button className="rounded-full bg-black px-4 py-2 text-sm text-white">Save</button>
      {message ? <p className="text-sm">{message}</p> : null}
    </form>
  );
}
