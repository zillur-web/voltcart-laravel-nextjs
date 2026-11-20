"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function CustomerPage() {
  const params = useParams<{ id: string }>();
  const [customer, setCustomer] = useState<{ name: string; email: string; orders: Order[] } | null>(null);

  useEffect(() => {
    api<{ name: string; email: string; orders: Order[] }>(`/admin/customers/${params.id}`, { auth: "admin" }).then((response) => setCustomer(response.data));
  }, [params.id]);

  if (!customer) return <p className="text-sm">Loading customer…</p>;

  return (
    <div>
      <h1 className="text-2xl font-bold">{customer.name}</h1>
      <p className="text-sm text-black/60">{customer.email}</p>
      <ul className="mt-6 divide-y divide-black/10 border border-black/10 bg-white">
        {customer.orders.map((order) => (
          <li key={order.id} className="flex justify-between px-4 py-3 text-sm">
            <Link href={`/admin/orders/${order.id}`} className="underline">
              {order.number}
            </Link>
            <span className="capitalize">{order.status}</span>
            <span>{money(order.total)}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
