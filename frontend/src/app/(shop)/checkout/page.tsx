"use client";

import { Elements, PaymentElement, useElements, useStripe } from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import { OrderSummary } from "@/components/shop/Summary";
import { ApiError, api, money } from "@/lib/api";
import type { Cart, Order } from "@/lib/types";

const publishable = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || "";
const stripePromise = publishable ? loadStripe(publishable) : null;

export default function CheckoutPage() {
  const router = useRouter();
  const [cart, setCart] = useState<Cart | null>(null);
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!localStorage.getItem("shopco_token")) {
      router.replace("/login?next=/checkout");
      return;
    }
    api<Cart>("/cart").then((response) => setCart(response.data)).catch(() => setMessage("Could not load checkout."));
  }, [router]);

  async function start(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const body = Object.fromEntries(form.entries());
    setBusy(true);
    setMessage("");
    try {
      const response = await api<{ order: Order; client_secret: string | null; paid: boolean }>("/checkout", { method: "POST", body });
      if (response.data.paid) {
        router.push(`/orders/${response.data.order.id}?confirmed=1`);
        return;
      }
      setClientSecret(response.data.client_secret);
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Checkout failed.");
    } finally {
      setBusy(false);
    }
  }

  if (!cart) return <main className="px-4 py-16 text-center">{message || "Loading checkout…"}</main>;

  return (
    <main className="mx-auto grid max-w-[1240px] gap-8 px-4 py-8 lg:grid-cols-[1.1fr_0.9fr]">
      <section>
        <h1 className="font-display text-4xl">CHECKOUT</h1>
        <p className="mt-2 text-sm text-black/50">
          Shipping and the total are confirmed on the server. <Link href="/cart" className="underline">Edit cart</Link>
        </p>
        {!clientSecret ? (
          <form onSubmit={start} className="mt-6 grid gap-3 sm:grid-cols-2">
            <Field name="name" label="Full name" className="sm:col-span-2" />
            <Field name="phone" label="Phone" />
            <Field name="country" label="Country" defaultValue="United States" />
            <Field name="line1" label="Address" className="sm:col-span-2" />
            <Field name="line2" label="Apartment" required={false} />
            <Field name="city" label="City" />
            <Field name="state" label="State" />
            <Field name="postal_code" label="Postal code" />
            <button disabled={busy || cart.items.length === 0} className="sm:col-span-2 rounded-full bg-black py-3 text-white disabled:opacity-40">
              {busy ? "Starting payment…" : `Continue to payment · ${money(cart.summary.total)}`}
            </button>
          </form>
        ) : stripePromise ? (
          <div className="mt-6">
            <Elements stripe={stripePromise} options={{ clientSecret }}>
              <PayForm />
            </Elements>
          </div>
        ) : (
          <p className="mt-6 text-sm text-sale">Add NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY to pay with a test card.</p>
        )}
        {message ? <p className="mt-4 text-sm text-sale">{message}</p> : null}
      </section>
      <OrderSummary cart={cart} />
    </main>
  );
}

function Field({ name, label, className = "", required = true, defaultValue = "" }: { name: string; label: string; className?: string; required?: boolean; defaultValue?: string }) {
  return (
    <label className={`block text-sm ${className}`}>
      <span className="text-black/60">{label}</span>
      <input name={name} required={required} defaultValue={defaultValue} className="mt-1 w-full rounded-xl border border-line px-3 py-3 outline-none" />
    </label>
  );
}

function PayForm() {
  const stripe = useStripe();
  const elements = useElements();
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);

  async function pay(event: FormEvent) {
    event.preventDefault();
    if (!stripe || !elements) return;
    setBusy(true);
    const result = await stripe.confirmPayment({
      elements,
      confirmParams: { return_url: `${window.location.origin}/checkout/complete` },
    });
    if (result.error) {
      setMessage(result.error.message || "Payment failed. The order was not marked as paid.");
      setBusy(false);
    }
  }

  return (
    <form onSubmit={pay} className="space-y-4">
      <PaymentElement />
      <button disabled={!stripe || busy} className="w-full rounded-full bg-black py-3 text-white disabled:opacity-40">
        {busy ? "Confirming…" : "Pay now"}
      </button>
      {message ? <p className="text-sm text-sale">{message}</p> : null}
      <p className="text-xs text-black/50">Test card 4242 4242 4242 4242, any future date, any CVC.</p>
    </form>
  );
}
