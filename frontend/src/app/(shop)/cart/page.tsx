"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { OrderSummary } from "@/components/shop/Summary";
import { ApiError, api, money } from "@/lib/api";
import type { Cart } from "@/lib/types";

export default function CartPage() {
  const [cart, setCart] = useState<Cart | null>(null);
  const [code, setCode] = useState("");
  const [message, setMessage] = useState("");

  async function load() {
    const response = await api<Cart>("/cart");
    setCart(response.data);
    setCode(response.data.coupon?.code || "");
  }

  useEffect(() => {
    load().catch(() => setMessage("Could not load the cart."));
  }, []);

  async function run(work: () => Promise<unknown>) {
    setMessage("");
    try {
      await work();
      await load();
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Something went wrong.");
    }
  }

  if (!cart) return <main className="px-4 py-16 text-center">{message || "Loading cart…"}</main>;

  return (
    <main className="mx-auto max-w-[1240px] px-4 py-8">
      <h1 className="font-display text-4xl md:text-5xl">YOUR CART</h1>
      <div className="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
        <section className="rounded-[20px] border border-line px-4 md:px-6">
          {cart.items.length === 0 ? <p className="py-12 text-black/50">Your cart is empty.</p> : null}
          {cart.items.map((item) => (
            <article key={item.id} className="grid grid-cols-[88px_1fr] gap-4 border-b border-line py-5 last:border-0">
              <div className="overflow-hidden rounded-[12px] bg-muted">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={item.product.image || ""} alt="" className="aspect-square w-full object-cover" />
              </div>
              <div>
                <div className="flex justify-between gap-3">
                  <div>
                    <h2 className="font-bold">{item.product.name}</h2>
                    <p className="mt-1 text-sm text-black/50">
                      Size: {item.size || "—"} · Color: {item.product.color}
                    </p>
                  </div>
                  <button className="text-sm text-sale" onClick={() => run(() => api(`/cart/items/${item.id}`, { method: "DELETE" }))}>
                    Remove
                  </button>
                </div>
                <div className="mt-3 flex items-center justify-between">
                  <p className="text-lg font-bold">{money(item.line_total)}</p>
                  <div className="flex items-center gap-4 rounded-full bg-muted px-4 py-2 text-sm">
                    <button onClick={() => run(() => api(`/cart/items/${item.id}`, { method: "PATCH", body: { quantity: Math.max(1, item.quantity - 1) } }))}>−</button>
                    <span>{item.quantity}</span>
                    <button onClick={() => run(() => api(`/cart/items/${item.id}`, { method: "PATCH", body: { quantity: item.quantity + 1 } }))}>+</button>
                  </div>
                </div>
              </div>
            </article>
          ))}
        </section>
        <OrderSummary
          cart={cart}
          code={code}
          setCode={setCode}
          onApply={() => run(() => api("/cart/coupon", { method: "POST", body: { code } }))}
          action={
            cart.items.length ? (
              <Link href="/checkout" className="block rounded-full bg-black py-4 text-center text-white">
                Go to Checkout
              </Link>
            ) : null
          }
        />
      </div>
      {message ? <p className="mt-4 text-sm text-sale">{message}</p> : null}
    </main>
  );
}
