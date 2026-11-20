import { Suspense } from "react";
import { ShopFooter, ShopHeader } from "@/components/shop/Chrome";

export default function ShopLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <ShopHeader />
      <Suspense fallback={<div className="px-4 py-16 text-center text-sm">Loading the shop…</div>}>{children}</Suspense>
      <ShopFooter />
    </>
  );
}
