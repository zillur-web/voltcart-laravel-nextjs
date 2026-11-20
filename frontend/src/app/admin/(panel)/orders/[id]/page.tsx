"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { can, useStaff } from "@/components/admin/Shell";
import { ApiError, api, money } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function AdminOrderPage() {
  const params = useParams<{ id: string }>();
  const { user } = useStaff();
  const [order, setOrder] = useState<Order | null>(null);
  const [message, setMessage] = useState("");

  function load() {
    api<Order>(`/admin/orders/${params.id}`, { auth: "admin" }).then((response) => setOrder(response.data));
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params.id]);

  if (!order) return <p className="text-sm">Loading order…</p>;
  const address = order.shipping_address;

  return (
    <div className="max-w-3xl space-y-4">
      <h1 className="text-2xl font-bold">{order.number}</h1>
      <p className="capitalize">Status: {order.status}</p>
      <p className="text-sm">
        {order.customer?.name} · {order.customer?.email}
      </p>
      {address ? (
        <p className="text-sm text-black/70">
          {address.name}, {address.phone}, {address.line1} {address.line2}, {address.city}, {address.state} {address.postal_code}, {address.country}
        </p>
      ) : null}
      <ul className="divide-y divide-black/10 border border-black/10 bg-white">
        {order.items.map((item) => (
          <li key={item.id} className="flex justify-between px-4 py-3 text-sm">
            <span>
              {item.name} · {item.sku} × {item.quantity}
            </span>
            <span>{money(item.line_total)}</span>
          </li>
        ))}
      </ul>
      <p className="font-bold">Total {money(order.total)}</p>
      {can(user, "orders.update_status") ? (
        <div className="flex flex-wrap gap-2">
          {order.allowed_transitions?.map((status) => (
            <button
              key={status}
              className="rounded-full border border-black px-4 py-2 text-sm capitalize"
              onClick={async () => {
                try {
                  const response = await api<Order>(`/admin/orders/${order.id}/status`, { method: "PATCH", auth: "admin", body: { status } });
                  setOrder(response.data);
                  setMessage(`Moved to ${status}. The customer was emailed.`);
                } catch (error) {
                  setMessage(error instanceof ApiError ? error.message : "Could not update status.");
                }
              }}
            >
              Mark {status}
            </button>
          ))}
        </div>
      ) : null}
      {message ? <p className="text-sm">{message}</p> : null}
    </div>
  );
}
