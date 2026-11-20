"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import type { StaffUser } from "@/lib/types";

const links = [
  { href: "/admin", label: "Dashboard", perm: "reports.view" },
  { href: "/admin/products", label: "Products", perm: "products.view" },
  { href: "/admin/categories", label: "Categories", perm: "categories.view" },
  { href: "/admin/orders", label: "Orders", perm: "orders.view" },
  { href: "/admin/coupons", label: "Coupons", perm: "coupons.manage" },
  { href: "/admin/customers", label: "Customers", perm: "customers.view" },
  { href: "/admin/roles", label: "Roles", perm: "roles.manage" },
  { href: "/admin/profile", label: "Profile", perm: "" },
];

export function useStaff() {
  const router = useRouter();
  const pathname = usePathname();
  const [user, setUser] = useState<StaffUser | null>(null);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    api<StaffUser>("/admin/auth/me", { auth: "admin" })
      .then((response) => setUser(response.data))
      .catch(() => router.replace("/admin/login"));
  }, [router]);

  useEffect(() => {
    if (!user) return;
    const allowed = links.filter((link) => !link.perm || user.permissions.includes(link.perm));
    if (pathname === "/admin" && !user.permissions.includes("reports.view") && allowed[0] && allowed[0].href !== "/admin") {
      router.replace(allowed[0].href);
    }
  }, [user, pathname, router]);

  return { user, open, setOpen };
}

export function AdminShell({ children }: { children: React.ReactNode }) {
  const { user, open, setOpen } = useStaff();
  const pathname = usePathname();
  const router = useRouter();
  if (!user) return <div className="p-10 text-sm text-black/50">Checking access…</div>;

  const visible = links.filter((link) => !link.perm || user.permissions.includes(link.perm));

  return (
    <div className="min-h-screen bg-[#f3f1ee] text-[#1c1c1c] md:grid md:grid-cols-[230px_1fr]">
      <aside className={`${open ? "block" : "hidden"} border-r border-black/10 bg-[#171717] text-white md:block`}>
        <div className="px-5 py-6">
          <p className="font-display text-2xl">SHOP.CO</p>
          <p className="mt-1 text-xs uppercase tracking-[0.16em] text-white/50">Staff</p>
        </div>
        <nav className="space-y-1 px-3">
          {visible.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              onClick={() => setOpen(false)}
              className={`block rounded-lg px-3 py-2 text-sm ${pathname === link.href ? "bg-white text-black" : "text-white/80"}`}
            >
              {link.label}
            </Link>
          ))}
        </nav>
      </aside>
      <div>
        <header className="flex items-center justify-between border-b border-black/10 bg-white px-4 py-4 md:px-8">
          <button className="md:hidden" onClick={() => setOpen((value) => !value)}>
            Menu
          </button>
          <p className="text-sm">{user.name}</p>
          <button
            className="text-sm underline"
            onClick={async () => {
              await api("/admin/auth/logout", { method: "POST", auth: "admin" }).catch(() => undefined);
              localStorage.removeItem("shopco_admin_token");
              router.push("/admin/login");
            }}
          >
            Sign out
          </button>
        </header>
        <div className="p-4 md:p-8">{children}</div>
      </div>
    </div>
  );
}

export function can(user: StaffUser | null, permission: string) {
  return Boolean(user?.permissions.includes(permission));
}
