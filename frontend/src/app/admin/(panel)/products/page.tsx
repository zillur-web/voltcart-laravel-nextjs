"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useStaff, can } from "@/components/admin/Shell";
import { ApiError, api, money } from "@/lib/api";
import type { Category, Product } from "@/lib/types";

export default function ProductsPage() {
  const { user } = useStaff();
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [q, setQ] = useState("");
  const [categoryId, setCategoryId] = useState("");
  const [message, setMessage] = useState("");

  async function load(search = q, category = categoryId) {
    const query = new URLSearchParams();
    if (search) query.set("q", search);
    if (category) query.set("category_id", category);
    const response = await api<Product[]>(`/admin/products?${query.toString()}`, { auth: "admin" });
    setProducts(response.data);
  }

  useEffect(() => {
    load().catch((error) => setMessage(error instanceof ApiError ? error.message : "Could not load products."));
    api<Category[]>("/admin/categories", { auth: "admin" }).then((response) => setCategories(response.data)).catch(() => undefined);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold">Products</h1>
        {can(user, "products.create") ? (
          <Link href="/admin/products/new" className="rounded-full bg-black px-4 py-2 text-sm text-white">
            New product
          </Link>
        ) : null}
      </div>
      <form
        className="mt-4 flex flex-wrap gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          load();
        }}
      >
        <input value={q} onChange={(event) => setQ(event.target.value)} placeholder="Search name or SKU" className="rounded-lg border border-black/10 bg-white px-3 py-2 text-sm" />
        <select value={categoryId} onChange={(event) => setCategoryId(event.target.value)} className="rounded-lg border border-black/10 bg-white px-3 py-2 text-sm">
          <option value="">All categories</option>
          {categories.flatMap((category) => [category, ...(category.children || [])]).map((category) => (
            <option key={category.id} value={category.id}>
              {category.parent_id ? `— ${category.name}` : category.name}
            </option>
          ))}
        </select>
        <button className="rounded-lg bg-black px-4 py-2 text-sm text-white">Search</button>
      </form>
      {message ? <p className="mt-3 text-sm text-sale">{message}</p> : null}
      <div className="mt-4 overflow-x-auto border border-black/10 bg-white">
        <table className="w-full text-left text-sm">
          <thead className="text-xs uppercase tracking-wide text-black/50">
            <tr>
              <th className="px-3 py-3">Product</th>
              <th className="px-3 py-3">SKU</th>
              <th className="px-3 py-3">Price</th>
              <th className="px-3 py-3">Stock</th>
              <th className="px-3 py-3">Status</th>
            </tr>
          </thead>
          <tbody>
            {products.map((product) => (
              <tr key={product.id} className="border-t border-black/5">
                <td className="px-3 py-3">
                  <Link href={`/admin/products/${product.id}`} className="font-medium underline">
                    {product.name}
                  </Link>
                </td>
                <td className="px-3 py-3">{product.sku}</td>
                <td className="px-3 py-3">{money(product.effective_price)}</td>
                <td className="px-3 py-3">{product.stock}</td>
                <td className="px-3 py-3">{product.is_active ? "Active" : "Hidden"}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
