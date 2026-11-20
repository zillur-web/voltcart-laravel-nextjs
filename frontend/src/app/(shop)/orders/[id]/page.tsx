"use client";

import Link from "next/link";
import { useParams, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function OrderDetailPage() {
  const params = useParams<{ id: string }>();
  const search = useSearchParams();
  const [order, setOrder] = useState<Order | null>(null);
  const [message, setMessage] = useState("");

  useEffect(() => {
    api<Order>(`/orders/${params.id}`)
      .then((response) => setOrder(response.data))
      .catch(() => setMessage("Order not found."));
  }, [params.id]);

  if (!order) return <main className="px-4 py-16 text-center">{message || "Loading order…"}</main>;
  const address = order.shipping_address;

  return (
    <main className="mx-auto max-w-[800px] px-4 py-10">
      {search.get("confirmed") ? <p className="mb-4 rounded-xl bg-muted px-4 py-3 text-sm">Order {order.number} is confirmed. Status: {order.status}.</p> : null}
      <p className="text-sm text-black/50">
        <Link href="/orders">My orders</Link>
      </p>
      <h1 className="mt-3 font-display text-4xl">{order.number}</h1>
      <p className="mt-2 capitalize text-black/60">{order.status}</p>
      <ul className="mt-8 divide-y divide-line border-y border-line">
        {order.items.map((item) => (
          <li key={item.id} className="flex justify-between py-4 text-sm">
            <span>
              {item.name} × {item.quantity}
            </span>
            <span className="font-bold">{money(item.line_total)}</span>
          </li>
        ))}
      </ul>
      <p className="mt-4 text-right text-lg font-bold">Total {money(order.total)}</p>
      {address ? (
        <p className="mt-6 text-sm text-black/70">
          {address.name}, {address.line1}, {address.city} {address.state} {address.postal_code}, {address.country}
        </p>
      ) : null}
    </main>
  );
}
