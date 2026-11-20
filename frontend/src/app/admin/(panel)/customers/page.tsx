"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";

type Customer = { id: number; name: string; email: string; orders_count: number };

export default function CustomersPage() {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [q, setQ] = useState("");

  function load(search = q) {
    api<Customer[]>(`/admin/customers?q=${encodeURIComponent(search)}`, { auth: "admin" }).then((response) => setCustomers(response.data));
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div>
      <h1 className="text-2xl font-bold">Customers</h1>
      <form
        className="mt-4 flex gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          load();
        }}
      >
        <input value={q} onChange={(event) => setQ(event.target.value)} placeholder="Search" className="rounded-lg border border-black/10 bg-white px-3 py-2 text-sm" />
        <button className="rounded-lg bg-black px-4 py-2 text-sm text-white">Search</button>
      </form>
      <ul className="mt-4 divide-y divide-black/10 border border-black/10 bg-white">
        {customers.map((customer) => (
          <li key={customer.id}>
            <Link href={`/admin/customers/${customer.id}`} className="flex justify-between px-4 py-3 text-sm">
              <span>
                {customer.name}
                <span className="ml-2 text-black/50">{customer.email}</span>
              </span>
              <span>{customer.orders_count} orders</span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
