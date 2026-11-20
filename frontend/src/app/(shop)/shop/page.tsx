"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { ProductCard } from "@/components/shop/ProductCard";
import { api, type Pagination } from "@/lib/api";
import type { Category, Product } from "@/lib/types";

const colors = ["Black", "White", "Blue", "Green", "Red", "Beige", "Gray", "Pink", "Yellow", "Orange", "Purple"];
const sizes = ["XS", "S", "M", "L", "XL", "30", "32", "34", "36", "7", "8", "9", "10", "11"];
const swatch: Record<string, string> = {
  Black: "#000",
  White: "#fff",
  Blue: "#063af5",
  Green: "#314f4a",
  Red: "#c63a3a",
  Beige: "#d6c4a8",
  Gray: "#9a9a9a",
  Pink: "#e7a6b5",
  Yellow: "#e6c84a",
  Orange: "#e67a3c",
  Purple: "#6b4c9a",
};

export default function ShopPage() {
  const params = useSearchParams();
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [meta, setMeta] = useState<Pagination | null>(null);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [minPrice, setMinPrice] = useState(params.get("min_price") || "0");
  const [maxPrice, setMaxPrice] = useState(params.get("max_price") || "300");

  const category = params.get("category") || "";
  const color = params.get("color") || "";
  const size = params.get("size") || "";
  const sort = params.get("sort") || "newest";
  const page = params.get("page") || "1";
  const q = params.get("q") || "";

  useEffect(() => {
    api<Category[]>("/categories", { auth: false }).then((response) => setCategories(response.data));
  }, []);

  useEffect(() => {
    const query = new URLSearchParams({ sort, page, per_page: "9" });
    if (q) query.set("q", q);
    if (category) query.set("category", category);
    if (color) query.set("color", color);
    if (size) query.set("size", size);
    if (minPrice) query.set("min_price", minPrice);
    if (maxPrice) query.set("max_price", maxPrice);
    api<Product[]>(`/products?${query.toString()}`, { auth: false }).then((response) => {
      setProducts(response.data);
      setMeta(response.meta || null);
    });
  }, [category, color, size, sort, page, q, minPrice, maxPrice]);

  function update(next: Record<string, string>) {
    const query = new URLSearchParams(params.toString());
    Object.entries(next).forEach(([key, value]) => {
      if (!value) query.delete(key);
      else query.set(key, value);
    });
    if (!("page" in next)) query.delete("page");
    router.push(`/shop?${query.toString()}`);
  }

  const filters = (
    <aside className="space-y-6 rounded-[20px] border border-line p-5">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-bold">Filters</h2>
        <button className="text-sm text-black/50" onClick={() => update({ category: "", color: "", size: "", min_price: "", max_price: "" })}>
          Clear
        </button>
      </div>
      <div>
        <p className="font-bold">Dress Style</p>
        <div className="mt-3 space-y-2 text-sm">
          {categories.map((parent) => (
            <div key={parent.id}>
              <button className={`block ${category === parent.slug ? "font-bold" : ""}`} onClick={() => update({ category: parent.slug })}>
                {parent.name}
              </button>
              <div className="ml-3 mt-1 space-y-1 text-black/70">
                {parent.children?.map((child) => (
                  <button key={child.id} className={`block ${category === child.slug ? "font-bold text-black" : ""}`} onClick={() => update({ category: child.slug })}>
                    {child.name}
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
      <div>
        <p className="font-bold">Price</p>
        <div className="mt-3 flex gap-2 text-sm">
          <input value={minPrice} onChange={(event) => setMinPrice(event.target.value)} className="w-full rounded-full border border-line px-3 py-2" />
          <input value={maxPrice} onChange={(event) => setMaxPrice(event.target.value)} className="w-full rounded-full border border-line px-3 py-2" />
        </div>
        <button className="mt-3 w-full rounded-full bg-black py-2 text-sm text-white" onClick={() => update({ min_price: minPrice, max_price: maxPrice })}>
          Apply
        </button>
      </div>
      <div>
        <p className="font-bold">Colors</p>
        <div className="mt-3 flex flex-wrap gap-2">
          {colors.map((item) => (
            <button
              key={item}
              aria-label={item}
              onClick={() => update({ color: color === item ? "" : item })}
              className={`h-9 w-9 rounded-full border ${color === item ? "ring-2 ring-black ring-offset-2" : "border-line"}`}
              style={{ background: swatch[item] }}
            />
          ))}
        </div>
      </div>
      <div>
        <p className="font-bold">Size</p>
        <div className="mt-3 flex flex-wrap gap-2">
          {sizes.map((item) => (
            <button
              key={item}
              onClick={() => update({ size: size === item ? "" : item })}
              className={`rounded-full px-4 py-2 text-sm ${size === item ? "bg-black text-white" : "bg-muted"}`}
            >
              {item}
            </button>
          ))}
        </div>
      </div>
    </aside>
  );

  return (
    <main className="mx-auto grid max-w-[1240px] gap-8 px-4 py-8 md:grid-cols-[280px_1fr]">
      <div className="hidden md:block">{filters}</div>
      {filtersOpen ? (
        <div className="fixed inset-0 z-50 bg-black/40 md:hidden" onClick={() => setFiltersOpen(false)}>
          <div className="ml-auto h-full w-[min(100%,360px)] overflow-auto bg-white p-4" onClick={(event) => event.stopPropagation()}>
            {filters}
            <button className="mt-4 w-full rounded-full bg-black py-3 text-white" onClick={() => setFiltersOpen(false)}>
              Apply Filter
            </button>
          </div>
        </div>
      ) : null}
      <section>
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="font-display text-3xl">{q ? `RESULTS FOR “${q.toUpperCase()}”` : category ? category.replace(/-/g, " ").toUpperCase() : "CASUAL"}</h1>
          <div className="flex items-center gap-3 text-sm">
            <button className="rounded-full border border-line px-4 py-2 md:hidden" onClick={() => setFiltersOpen(true)}>
              Filters
            </button>
            <label>
              Sort by{" "}
              <select value={sort} onChange={(event) => update({ sort: event.target.value })} className="bg-transparent font-medium outline-none">
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
              </select>
            </label>
          </div>
        </div>
        <div className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
        {products.length === 0 ? <p className="py-16 text-center text-black/50">No products match these filters.</p> : null}
        {meta && meta.last_page > 1 ? (
          <div className="mt-8 flex items-center justify-between border-t border-line pt-5 text-sm">
            <button disabled={meta.current_page === 1} className="rounded-lg border border-line px-4 py-2 disabled:opacity-40" onClick={() => update({ page: String(meta.current_page - 1) })}>
              Previous
            </button>
            <span>
              {meta.current_page} / {meta.last_page}
            </span>
            <button disabled={meta.current_page === meta.last_page} className="rounded-lg border border-line px-4 py-2 disabled:opacity-40" onClick={() => update({ page: String(meta.current_page + 1) })}>
              Next
            </button>
          </div>
        ) : null}
      </section>
    </main>
  );
}
