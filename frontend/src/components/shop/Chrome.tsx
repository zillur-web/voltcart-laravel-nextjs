"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import { api } from "@/lib/api";
import type { Cart } from "@/lib/types";

export function useCustomer() {
  const [name, setName] = useState<string | null>(null);
  useEffect(() => {
    const raw = localStorage.getItem("shopco_user");
    setName(raw ? JSON.parse(raw).name : null);
  }, []);
  return name;
}

export function ShopHeader() {
  const router = useRouter();
  const pathname = usePathname();
  const name = useCustomer();
  const [count, setCount] = useState(0);
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");

  useEffect(() => {
    api<Cart>("/cart")
      .then((response) => setCount(response.data.items.reduce((sum, item) => sum + item.quantity, 0)))
      .catch(() => setCount(0));
  }, [pathname]);

  function search(event: FormEvent) {
    event.preventDefault();
    setOpen(false);
    router.push(`/shop?q=${encodeURIComponent(query)}`);
  }

  const links = [
    { href: "/shop", label: "Shop" },
    { href: "/shop?sort=price_asc", label: "On Sale" },
    { href: "/shop?sort=newest", label: "New Arrivals" },
    { href: "/#brands", label: "Brands" },
  ];

  return (
    <header className="sticky top-0 z-40 bg-white">
      <div className="bg-black px-4 py-2 text-center text-xs text-white sm:text-sm">
        Sign up and get 20% off your first order.{" "}
        <Link href="/register" className="font-medium underline">
          Sign Up Now
        </Link>
      </div>
      <div className="mx-auto flex max-w-[1240px] items-center gap-4 px-4 py-5">
        <button className="text-2xl md:hidden" onClick={() => setOpen((value) => !value)} aria-label="Menu">
          ☰
        </button>
        <Link href="/" className="font-display text-2xl tracking-tight md:text-[32px]">
          SHOP.CO
        </Link>
        <nav className="ml-6 hidden items-center gap-6 text-sm md:flex">
          {links.map((link) => (
            <Link key={link.label} href={link.href}>
              {link.label}
            </Link>
          ))}
        </nav>
        <form onSubmit={search} className="ml-auto hidden min-w-0 flex-1 md:block">
          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search for products..."
            className="w-full rounded-full bg-muted px-5 py-3 text-sm outline-none"
          />
        </form>
        <div className="ml-auto flex items-center gap-4 md:ml-4">
          <Link href="/cart" className="relative text-sm font-medium" aria-label="Cart">
            Cart
            <span className="ml-1 rounded-full bg-black px-1.5 text-xs text-white">{count}</span>
          </Link>
          {name ? (
            <Link href="/orders" className="text-sm font-medium">
              {name.split(" ")[0]}
            </Link>
          ) : (
            <Link href="/login" className="text-sm font-medium">
              Account
            </Link>
          )}
        </div>
      </div>
      {open ? (
        <div className="space-y-3 border-t border-line px-4 py-4 md:hidden">
          <form onSubmit={search}>
            <input
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Search for products..."
              className="w-full rounded-full bg-muted px-4 py-3 text-sm outline-none"
            />
          </form>
          {links.map((link) => (
            <Link key={link.label} href={link.href} className="block" onClick={() => setOpen(false)}>
              {link.label}
            </Link>
          ))}
        </div>
      ) : null}
    </header>
  );
}

export function ShopFooter() {
  return (
    <footer className="relative mt-24 bg-soft">
      <div className="mx-auto max-w-[1240px] px-4">
        <form
          className="relative -top-16 grid gap-6 rounded-[20px] bg-black px-6 py-8 text-white md:grid-cols-[1.2fr_1fr] md:px-16 md:py-10"
          onSubmit={(event) => {
            event.preventDefault();
            const data = new FormData(event.currentTarget);
            if (!String(data.get("email") || "").includes("@")) return;
            event.currentTarget.reset();
            alert("You are on the list. Use WELCOME20 at checkout.");
          }}
        >
          <h2 className="font-display text-3xl leading-none md:text-4xl">STAY UP TO DATE ABOUT OUR LATEST OFFERS</h2>
          <div className="flex flex-col gap-3">
            <input name="email" type="email" required placeholder="Enter your email address" className="rounded-full bg-white px-5 py-3 text-sm text-black outline-none" />
            <button className="rounded-full bg-white px-5 py-3 text-sm font-medium text-black">Subscribe to Newsletter</button>
          </div>
        </form>
        <div className="grid gap-8 pb-10 md:grid-cols-[1.4fr_1fr_1fr_1fr_1fr]">
          <div>
            <p className="font-display text-3xl">SHOP.CO</p>
            <p className="mt-4 max-w-xs text-sm text-black/60">
              We have clothes that suit your style and which you&apos;re proud to wear. From women to men.
            </p>
          </div>
          {[
            ["COMPANY", ["About", "Features", "Works", "Career"]],
            ["HELP", ["Customer Support", "Delivery Details", "Terms & Conditions", "Privacy Policy"]],
            ["FAQ", ["Account", "Manage Deliveries", "Orders", "Payments"]],
            ["RESOURCES", ["Free eBooks", "Development Tutorial", "How to - Blog", "Youtube Playlist"]],
          ].map(([title, items]) => (
            <div key={String(title)}>
              <p className="text-sm font-medium tracking-[0.12em]">{title as string}</p>
              <ul className="mt-4 space-y-3 text-sm text-black/60">
                {(items as string[]).map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
        <div className="flex flex-col gap-3 border-t border-black/10 py-6 text-sm text-black/60 md:flex-row md:justify-between">
          <p>Shop.co © 2000-2026, All Rights Reserved</p>
          <p>Visa · Mastercard · PayPal · Apple Pay</p>
        </div>
      </div>
    </footer>
  );
}
