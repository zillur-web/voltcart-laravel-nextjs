import Link from "next/link";
import { money } from "@/lib/api";
import type { Product } from "@/lib/types";

export function Stars({ value }: { value: number }) {
  const full = Math.round(value);
  return (
    <span className="inline-flex items-center gap-1 text-sm">
      <span className="tracking-tight text-star" aria-hidden>
        {"★★★★★".slice(0, full)}
        <span className="text-black/20">{"★★★★★".slice(full)}</span>
      </span>
      <span className="text-black/70">
        {value.toFixed(1)}
        <span className="text-black/40">/5</span>
      </span>
    </span>
  );
}

export function discountPercent(product: Product) {
  if (!product.discount_price) return null;
  const price = Number(product.price);
  const sale = Number(product.discount_price);
  if (sale >= price) return null;
  return Math.round(((price - sale) / price) * 100);
}

export function ProductCard({ product }: { product: Product }) {
  const off = discountPercent(product);
  return (
    <Link href={`/product/${product.slug}`} className="group block">
      <div className="overflow-hidden rounded-[20px] bg-muted">
        {product.image ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={product.image} alt={product.name} className="shot aspect-square w-full object-cover" />
        ) : (
          <div className="aspect-square" />
        )}
      </div>
      <h3 className="mt-4 text-lg font-bold leading-tight">{product.name}</h3>
      <div className="mt-1">
        <Stars value={product.rating} />
      </div>
      <p className="mt-2 flex flex-wrap items-center gap-2 text-xl font-bold">
        {money(product.effective_price)}
        {off ? (
          <>
            <span className="text-xl font-bold text-black/40 line-through">{money(product.price)}</span>
            <span className="rounded-full bg-sale/10 px-3 py-1 text-xs font-medium text-sale">-{off}%</span>
          </>
        ) : null}
      </p>
    </Link>
  );
}
