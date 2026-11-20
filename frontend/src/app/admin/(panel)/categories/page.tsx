"use client";

import { FormEvent, useEffect, useState } from "react";
import { can, useStaff } from "@/components/admin/Shell";
import { ApiError, api } from "@/lib/api";
import type { Category } from "@/lib/types";

export default function CategoriesPage() {
  const { user } = useStaff();
  const [categories, setCategories] = useState<Category[]>([]);
  const [message, setMessage] = useState("");

  async function load() {
    const response = await api<Category[]>("/admin/categories", { auth: "admin" });
    setCategories(response.data);
  }

  useEffect(() => {
    load().catch((error) => setMessage(error instanceof ApiError ? error.message : "Could not load categories."));
  }, []);

  async function create(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      await api("/admin/categories", {
        method: "POST",
        auth: "admin",
        body: {
          name: form.get("name"),
          parent_id: form.get("parent_id") || null,
          is_active: true,
          sort_order: 0,
        },
      });
      event.currentTarget.reset();
      await load();
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not create the category.");
    }
  }

  const rows = categories.flatMap((category) => [category, ...(category.children || [])]);

  return (
    <div>
      <h1 className="text-2xl font-bold">Categories</h1>
      {can(user, "categories.manage") ? (
        <form onSubmit={create} className="mt-4 flex flex-wrap gap-2">
          <input name="name" required placeholder="Name" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
          <select name="parent_id" className="rounded-lg border border-black/10 bg-white px-3 py-2">
            <option value="">Top level</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
          <button className="rounded-full bg-black px-4 py-2 text-sm text-white">Add</button>
        </form>
      ) : null}
      {message ? <p className="mt-3 text-sm text-sale">{message}</p> : null}
      <ul className="mt-6 divide-y divide-black/10 border border-black/10 bg-white">
        {rows.map((category) => (
          <li key={category.id} className="flex items-center justify-between px-4 py-3 text-sm">
            <span>{category.parent_id ? `— ${category.name}` : category.name}</span>
            {can(user, "categories.manage") ? (
              <button
                className="text-sale"
                onClick={async () => {
                  try {
                    await api(`/admin/categories/${category.id}`, { method: "DELETE", auth: "admin" });
                    await load();
                  } catch (error) {
                    setMessage(error instanceof ApiError ? error.message : "Could not delete.");
                  }
                }}
              >
                Delete
              </button>
            ) : null}
          </li>
        ))}
      </ul>
    </div>
  );
}
