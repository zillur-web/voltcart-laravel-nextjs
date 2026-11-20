"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { Category, Product } from "@/lib/types";

export function ProductForm({ product }: { product?: Product }) {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [message, setMessage] = useState("");

  useEffect(() => {
    api<Category[]>("/admin/categories", { auth: "admin" }).then((response) => setCategories(response.data));
  }, []);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const payload = new FormData();
    ["name", "description", "price", "discount_price", "sku", "stock", "category_id", "color", "is_active"].forEach((key) => {
      payload.append(key, String(form.get(key) ?? ""));
    });
    String(form.get("sizes") || "")
      .split(",")
      .map((size) => size.trim())
      .filter(Boolean)
      .forEach((size) => payload.append("sizes[]", size));
    const images = form.getAll("images");
    images.forEach((file) => {
      if (file instanceof File && file.size) payload.append("images[]", file);
    });
    if (!payload.get("discount_price")) payload.delete("discount_price");
    try {
      if (product) {
        await api(`/admin/products/${product.id}`, { method: "POST", auth: "admin", form: payload });
      } else {
        await api("/admin/products", { method: "POST", auth: "admin", form: payload });
      }
      router.push("/admin/products");
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not save the product.");
    }
  }

  const options = categories.flatMap((category) => [category, ...(category.children || [])]);

  return (
    <form onSubmit={submit} className="grid max-w-3xl gap-3">
      <input name="name" required defaultValue={product?.name} placeholder="Name" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
      <textarea name="description" required defaultValue={product?.description} placeholder="Description" className="min-h-28 rounded-lg border border-black/10 bg-white px-3 py-2" />
      <div className="grid gap-3 sm:grid-cols-2">
        <input name="price" required type="number" step="0.01" min="0" defaultValue={product?.price} placeholder="Price" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
        <input name="discount_price" type="number" step="0.01" min="0" defaultValue={product?.discount_price || ""} placeholder="Discount price" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
        <input name="sku" required defaultValue={product?.sku} placeholder="SKU" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
        <input name="stock" required type="number" min="0" defaultValue={product?.stock ?? 0} placeholder="Stock" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
        <select name="category_id" required defaultValue={product?.category_id || ""} className="rounded-lg border border-black/10 bg-white px-3 py-2">
          <option value="">Category</option>
          {options.map((category) => (
            <option key={category.id} value={category.id}>
              {category.parent_id ? `— ${category.name}` : category.name}
            </option>
          ))}
        </select>
        <input name="color" defaultValue={product?.color || ""} placeholder="Color" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
      </div>
      <input name="sizes" defaultValue={product?.sizes.join(", ") || ""} placeholder="Sizes, comma separated" className="rounded-lg border border-black/10 bg-white px-3 py-2" />
      <label className="text-sm">
        Active
        <select name="is_active" defaultValue={product?.is_active === false ? "0" : "1"} className="ml-2 rounded-lg border border-black/10 bg-white px-3 py-2">
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </label>
      <input name="images" type="file" accept="image/*" multiple className="text-sm" />
      <button className="w-fit rounded-full bg-black px-5 py-2 text-sm text-white">Save product</button>
      {message ? <p className="text-sm text-sale">{message}</p> : null}
    </form>
  );
}
