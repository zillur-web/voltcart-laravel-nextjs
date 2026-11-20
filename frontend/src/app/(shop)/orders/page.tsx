"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function OrdersPage() {
  const router = useRouter();
  const [orders, setOrders] = useState<Order[]>([]);

  useEffect(() => {
    if (!localStorage.getItem("shopco_token")) {
      router.replace("/login?next=/orders");
      return;
    }
    api<Order[]>("/orders").then((response) => setOrders(response.data));
  }, [router]);

  return (
    <main className="mx-auto max-w-[900px] px-4 py-10">
      <div className="flex items-center justify-between">
        <h1 className="font-display text-4xl">MY ORDERS</h1>
        <button
          className="text-sm underline"
          onClick={async () => {
            await api("/auth/logout", { method: "POST" }).catch(() => undefined);
            localStorage.removeItem("shopco_token");
            localStorage.removeItem("shopco_user");
            router.push("/");
          }}
        >
          Sign out
        </button>
      </div>
      <div className="mt-8 divide-y divide-line border-y border-line">
        {orders.map((order) => (
          <Link key={order.id} href={`/orders/${order.id}`} className="flex items-center justify-between py-4">
            <span>
              <span className="font-bold">{order.number}</span>
              <span className="ml-3 text-sm capitalize text-black/50">{order.status}</span>
            </span>
            <span className="font-bold">{money(order.total)}</span>
          </Link>
        ))}
        {orders.length === 0 ? <p className="py-10 text-black/50">No orders yet.</p> : null}
      </div>
    </main>
  );
}
