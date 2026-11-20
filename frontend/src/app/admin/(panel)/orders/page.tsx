"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function OrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [status, setStatus] = useState("");
  const [q, setQ] = useState("");

  function load(nextStatus = status, nextQ = q) {
    const query = new URLSearchParams();
    if (nextStatus) query.set("status", nextStatus);
    if (nextQ) query.set("q", nextQ);
    api<Order[]>(`/admin/orders?${query.toString()}`, { auth: "admin" }).then((response) => setOrders(response.data));
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div>
      <h1 className="text-2xl font-bold">Orders</h1>
      <form
        className="mt-4 flex flex-wrap gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          load();
        }}
      >
        <input value={q} onChange={(event) => setQ(event.target.value)} placeholder="Order number or email" className="rounded-lg border border-black/10 bg-white px-3 py-2 text-sm" />
        <select value={status} onChange={(event) => setStatus(event.target.value)} className="rounded-lg border border-black/10 bg-white px-3 py-2 text-sm">
          <option value="">All statuses</option>
          {["pending", "paid", "processing", "shipped", "delivered", "cancelled"].map((item) => (
            <option key={item}>{item}</option>
          ))}
        </select>
        <button className="rounded-lg bg-black px-4 py-2 text-sm text-white">Filter</button>
      </form>
      <table className="mt-4 w-full bg-white text-left text-sm">
        <tbody>
          {orders.map((order) => (
            <tr key={order.id} className="border-t border-black/5">
              <td className="px-3 py-3">
                <Link className="underline" href={`/admin/orders/${order.id}`}>
                  {order.number}
                </Link>
              </td>
              <td className="px-3 py-3">{order.email}</td>
              <td className="px-3 py-3 capitalize">{order.status}</td>
              <td className="px-3 py-3">{money(order.total)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
