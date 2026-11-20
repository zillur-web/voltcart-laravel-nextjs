"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function CompletePage() {
  const params = useSearchParams();
  const router = useRouter();
  const [message, setMessage] = useState("Confirming your payment…");

  useEffect(() => {
    const intent = params.get("payment_intent");
    const status = params.get("redirect_status");
    if (!intent) {
      setMessage("Missing payment reference.");
      return;
    }
    if (status && status !== "succeeded") {
      setMessage("Payment did not succeed. The order was not marked as paid.");
      return;
    }
    api<Order>("/checkout/confirm", { method: "POST", body: { payment_intent_id: intent } })
      .then((response) => router.replace(`/orders/${response.data.id}?confirmed=1`))
      .catch((error) => setMessage(error instanceof ApiError ? error.message : "Could not confirm the order."));
  }, [params, router]);

  return (
    <main className="mx-auto max-w-lg px-4 py-20 text-center">
      <h1 className="font-display text-4xl">PAYMENT</h1>
      <p className="mt-4 text-black/70">{message}</p>
      <Link href="/orders" className="mt-6 inline-block underline">
        My orders
      </Link>
    </main>
  );
}
