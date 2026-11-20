"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";

type Dashboard = {
  today_orders: number;
  today_revenue: string;
  customers: number;
  low_stock: number;
  sales: { date: string; total: string }[];
  recent_orders: { id: number; number: string; status: string; total: string; customer: string }[];
};

export default function DashboardPage() {
  const [data, setData] = useState<Dashboard | null>(null);
  const [message, setMessage] = useState("");

  useEffect(() => {
    api<Dashboard>("/admin/dashboard", { auth: "admin" })
      .then((response) => setData(response.data))
      .catch(() => setMessage("You do not have access to reports."));
  }, []);

  if (!data) return <p className="text-sm">{message || "Loading dashboard…"}</p>;
  const values = data.sales.map((day) => Number(day.total));
  const max = Math.max(...values, 1);
  const points = values.map((value, index) => `${(index / Math.max(values.length - 1, 1)) * 640},${160 - (value / max) * 140}`).join(" ");

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Today</h1>
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        {[
          ["Orders", String(data.today_orders)],
          ["Revenue", money(data.today_revenue)],
          ["Customers", String(data.customers)],
          ["Low stock", String(data.low_stock)],
        ].map(([label, value]) => (
          <div key={label} className="border border-black/10 bg-white px-4 py-4">
            <p className="text-xs uppercase tracking-[0.14em] text-black/50">{label}</p>
            <p className="mt-2 text-2xl font-bold">{value}</p>
          </div>
        ))}
      </div>
      <section className="border border-black/10 bg-white p-4">
        <h2 className="font-bold">Sales, last 30 days</h2>
        <svg viewBox="0 0 640 180" className="mt-4 h-48 w-full">
          <polyline fill="none" stroke="#171717" strokeWidth="2" points={points} />
        </svg>
      </section>
      <section className="border border-black/10 bg-white">
        <h2 className="border-b border-black/10 px-4 py-3 font-bold">Recent orders</h2>
        <table className="w-full text-left text-sm">
          <tbody>
            {data.recent_orders.map((order) => (
              <tr key={order.id} className="border-t border-black/5">
                <td className="px-4 py-3">
                  <Link href={`/admin/orders/${order.id}`} className="font-medium underline">
                    {order.number}
                  </Link>
                </td>
                <td className="px-4 py-3">{order.customer}</td>
                <td className="px-4 py-3 capitalize">{order.status}</td>
                <td className="px-4 py-3">{money(order.total)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  );
}
