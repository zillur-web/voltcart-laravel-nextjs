"use client";

import { FormEvent, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";

type Coupon = {
  id: number;
  code: string;
  type: "percent" | "fixed";
  value: string;
  min_order_amount: string;
  usage_limit: number | null;
  used_count: number;
  starts_at: string | null;
  ends_at: string | null;
  is_active: boolean;
};

const empty = { code: "", type: "percent", value: "", min_order_amount: "0", usage_limit: "", starts_at: "", ends_at: "", is_active: true };

export default function CouponsPage() {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [form, setForm] = useState({ ...empty, id: 0 });
  const [message, setMessage] = useState("");

  async function load() {
    const response = await api<Coupon[]>("/admin/coupons", { auth: "admin" });
    setCoupons(response.data);
  }

  useEffect(() => {
    load().catch((error) => setMessage(error instanceof ApiError ? error.message : "Could not load coupons."));
  }, []);

  async function save(event: FormEvent) {
    event.preventDefault();
    const body = {
      ...form,
      usage_limit: form.usage_limit ? Number(form.usage_limit) : null,
      starts_at: form.starts_at || null,
      ends_at: form.ends_at || null,
    };
    try {
      if (form.id) await api(`/admin/coupons/${form.id}`, { method: "PUT", auth: "admin", body });
      else await api("/admin/coupons", { method: "POST", auth: "admin", body });
      setForm({ ...empty, id: 0 });
      await load();
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not save the coupon.");
    }
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
      <div>
        <h1 className="text-2xl font-bold">Coupons</h1>
        <ul className="mt-4 divide-y divide-black/10 border border-black/10 bg-white">
          {coupons.map((coupon) => (
            <li key={coupon.id} className="flex items-center justify-between px-4 py-3 text-sm">
              <button className="text-left" onClick={() => setForm({ ...coupon, usage_limit: coupon.usage_limit ? String(coupon.usage_limit) : "", starts_at: coupon.starts_at?.slice(0, 10) || "", ends_at: coupon.ends_at?.slice(0, 10) || "", id: coupon.id })}>
                <span className="font-bold">{coupon.code}</span>
                <span className="ml-2 text-black/50">
                  {coupon.type === "percent" ? `${coupon.value}%` : `$${coupon.value}`} · used {coupon.used_count}
                  {coupon.usage_limit ? `/${coupon.usage_limit}` : ""}
                </span>
              </button>
              <button
                className="text-sale"
                onClick={async () => {
                  await api(`/admin/coupons/${coupon.id}`, { method: "DELETE", auth: "admin" });
                  await load();
                }}
              >
                Delete
              </button>
            </li>
          ))}
        </ul>
      </div>
      <form onSubmit={save} className="space-y-2 border border-black/10 bg-white p-4">
        <h2 className="font-bold">{form.id ? "Edit coupon" : "New coupon"}</h2>
        <input value={form.code} onChange={(event) => setForm({ ...form, code: event.target.value })} placeholder="Code" required className="w-full rounded border border-black/10 px-3 py-2" />
        <select value={form.type} onChange={(event) => setForm({ ...form, type: event.target.value as "percent" | "fixed" })} className="w-full rounded border border-black/10 px-3 py-2">
          <option value="percent">Percentage</option>
          <option value="fixed">Fixed amount</option>
        </select>
        <input value={form.value} onChange={(event) => setForm({ ...form, value: event.target.value })} placeholder="Value" required type="number" step="0.01" className="w-full rounded border border-black/10 px-3 py-2" />
        <input value={form.min_order_amount} onChange={(event) => setForm({ ...form, min_order_amount: event.target.value })} placeholder="Minimum order" type="number" step="0.01" className="w-full rounded border border-black/10 px-3 py-2" />
        <input value={form.usage_limit} onChange={(event) => setForm({ ...form, usage_limit: event.target.value })} placeholder="Usage limit" type="number" className="w-full rounded border border-black/10 px-3 py-2" />
        <input value={form.starts_at} onChange={(event) => setForm({ ...form, starts_at: event.target.value })} type="date" className="w-full rounded border border-black/10 px-3 py-2" />
        <input value={form.ends_at} onChange={(event) => setForm({ ...form, ends_at: event.target.value })} type="date" className="w-full rounded border border-black/10 px-3 py-2" />
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> Active
        </label>
        <button className="rounded-full bg-black px-4 py-2 text-sm text-white">Save</button>
        {message ? <p className="text-sm text-sale">{message}</p> : null}
      </form>
    </div>
  );
}
