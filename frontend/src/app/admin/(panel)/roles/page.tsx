"use client";

import { FormEvent, useEffect, useState } from "react";
import { can, useStaff } from "@/components/admin/Shell";
import { ApiError, api } from "@/lib/api";
import type { StaffUser } from "@/lib/types";

type Permission = { id: number; name: string; slug: string; group: string };
type Role = { id: number; name: string; description: string | null; is_super: boolean; permissions: number[] };

export default function RolesPage() {
  const { user } = useStaff();
  const [roles, setRoles] = useState<Role[]>([]);
  const [permissions, setPermissions] = useState<Permission[]>([]);
  const [draft, setDraft] = useState({ id: 0, name: "", description: "", permissions: [] as number[] });
  const [staff, setStaff] = useState<StaffUser[]>([]);
  const [roleOptions, setRoleOptions] = useState<Role[]>([]);
  const [message, setMessage] = useState("");

  async function load() {
    const response = await api<{ roles: Role[]; permissions: Permission[] }>("/admin/roles", { auth: "admin" });
    setRoles(response.data.roles);
    setPermissions(response.data.permissions);
    if (user && can(user, "staff.manage")) {
      const people = await api<{ staff: StaffUser[]; roles: Role[] }>("/admin/staff", { auth: "admin" });
      setStaff(people.data.staff);
      setRoleOptions(people.data.roles);
    }
  }

  useEffect(() => {
    if (user) load().catch((error) => setMessage(error instanceof ApiError ? error.message : "Could not load roles."));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user]);

  function toggle(id: number) {
    setDraft((current) => ({
      ...current,
      permissions: current.permissions.includes(id) ? current.permissions.filter((item) => item !== id) : [...current.permissions, id],
    }));
  }

  async function saveRole(event: FormEvent) {
    event.preventDefault();
    const body = { name: draft.name, description: draft.description, permissions: draft.permissions };
    try {
      if (draft.id) await api(`/admin/roles/${draft.id}`, { method: "PUT", auth: "admin", body });
      else await api("/admin/roles", { method: "POST", auth: "admin", body });
      setDraft({ id: 0, name: "", description: "", permissions: [] });
      await load();
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not save the role.");
    }
  }

  const groups = [...new Set(permissions.map((permission) => permission.group))];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold">Roles and permissions</h1>
        <div className="mt-4 overflow-x-auto border border-black/10 bg-white">
          <table className="w-full text-left text-xs">
            <thead>
              <tr>
                <th className="px-3 py-3">Permission</th>
                {roles.map((role) => (
                  <th key={role.id} className="px-3 py-3">{role.name}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {permissions.map((permission) => (
                <tr key={permission.id} className="border-t border-black/5">
                  <td className="px-3 py-2">{permission.slug}</td>
                  {roles.map((role) => (
                    <td key={role.id} className="px-3 py-2">{role.is_super || role.permissions.includes(permission.id) ? "Yes" : "—"}</td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="mt-3 flex flex-wrap gap-2 text-sm">
          {roles.filter((role) => !role.is_super).map((role) => (
            <button key={role.id} className="underline" onClick={() => setDraft({ id: role.id, name: role.name, description: role.description || "", permissions: role.permissions })}>
              Edit {role.name}
            </button>
          ))}
        </div>
      </div>
      <form onSubmit={saveRole} className="max-w-3xl border border-black/10 bg-white p-4">
        <h2 className="font-bold">{draft.id ? "Edit role" : "New role"}</h2>
        <input value={draft.name} onChange={(event) => setDraft({ ...draft, name: event.target.value })} required placeholder="Role name" className="mt-3 w-full rounded border border-black/10 px-3 py-2" />
        <input value={draft.description} onChange={(event) => setDraft({ ...draft, description: event.target.value })} placeholder="Description" className="mt-2 w-full rounded border border-black/10 px-3 py-2" />
        <div className="mt-4 grid gap-4 md:grid-cols-2">
          {groups.map((group) => (
            <fieldset key={group}>
              <legend className="text-xs uppercase tracking-wide text-black/50">{group}</legend>
              {permissions.filter((permission) => permission.group === group).map((permission) => (
                <label key={permission.id} className="mt-1 flex items-center gap-2 text-sm">
                  <input type="checkbox" checked={draft.permissions.includes(permission.id)} onChange={() => toggle(permission.id)} />
                  {permission.name}
                </label>
              ))}
            </fieldset>
          ))}
        </div>
        <button className="mt-4 rounded-full bg-black px-4 py-2 text-sm text-white">Save role</button>
      </form>
      {can(user, "staff.manage") ? (
        <section>
          <h2 className="text-xl font-bold">Staff</h2>
          <form
            className="mt-3 grid max-w-xl gap-2"
            onSubmit={async (event) => {
              event.preventDefault();
              const form = new FormData(event.currentTarget);
              try {
                await api("/admin/staff", {
                  method: "POST",
                  auth: "admin",
                  body: {
                    name: form.get("name"),
                    email: form.get("email"),
                    password: form.get("password"),
                    password_confirmation: form.get("password_confirmation"),
                    roles: [Number(form.get("role"))],
                  },
                });
                event.currentTarget.reset();
                await load();
              } catch (error) {
                setMessage(error instanceof ApiError ? error.message : "Could not add staff.");
              }
            }}
          >
            <input name="name" required placeholder="Name" className="rounded border border-black/10 px-3 py-2" />
            <input name="email" type="email" required placeholder="Email" className="rounded border border-black/10 px-3 py-2" />
            <input name="password" type="password" required placeholder="Password" className="rounded border border-black/10 px-3 py-2" />
            <input name="password_confirmation" type="password" required placeholder="Confirm password" className="rounded border border-black/10 px-3 py-2" />
            <select name="role" className="rounded border border-black/10 px-3 py-2">
              {roleOptions.filter((role) => !role.is_super).map((role) => (
                <option key={role.id} value={role.id}>{role.name}</option>
              ))}
            </select>
            <button className="w-fit rounded-full bg-black px-4 py-2 text-sm text-white">Add staff</button>
          </form>
          <ul className="mt-4 divide-y divide-black/10 border border-black/10 bg-white">
            {staff.map((member) => (
              <li key={member.id} className="flex items-center justify-between px-4 py-3 text-sm">
                <span>
                  {member.name} · {member.email}
                  <span className="ml-2 text-black/50">{member.roles.map((role) => role.name).join(", ")}</span>
                </span>
                {!member.is_super_admin ? (
                  <button className="text-sale" onClick={async () => { await api(`/admin/staff/${member.id}`, { method: "DELETE", auth: "admin" }); await load(); }}>
                    Delete
                  </button>
                ) : (
                  <span className="text-xs uppercase tracking-wide text-black/40">Protected</span>
                )}
              </li>
            ))}
          </ul>
        </section>
      ) : null}
      {message ? <p className="text-sm text-sale">{message}</p> : null}
    </div>
  );
}
