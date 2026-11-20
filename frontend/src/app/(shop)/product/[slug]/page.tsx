"use client";

import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ProductCard, Stars, discountPercent } from "@/components/shop/ProductCard";
import { ApiError, api, money } from "@/lib/api";
import type { Product } from "@/lib/types";

export default function ProductPage() {
  const params = useParams<{ slug: string }>();
  const router = useRouter();
  const [product, setProduct] = useState<Product | null>(null);
  const [related, setRelated] = useState<Product[]>([]);
  const [image, setImage] = useState(0);
  const [size, setSize] = useState<string>("");
  const [qty, setQty] = useState(1);
  const [message, setMessage] = useState("");

  useEffect(() => {
    api<{ product: Product; related: Product[] }>(`/products/${params.slug}`, { auth: false }).then((response) => {
      setProduct(response.data.product);
      setRelated(response.data.related);
      setSize(response.data.product.sizes[0] || "");
    }).catch(() => setMessage("Product not found."));
  }, [params.slug]);

  async function addToCart() {
    if (!product) return;
    setMessage("");
    try {
      await api("/cart/items", { method: "POST", body: { product_id: product.id, quantity: qty, size } });
      router.push("/cart");
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : "Could not add this product.");
    }
  }

  if (!product) {
    return <main className="px-4 py-20 text-center">{message || "Loading…"}</main>;
  }

  const off = discountPercent(product);

  return (
    <main className="mx-auto max-w-[1240px] px-4 py-8">
      <p className="text-sm text-black/50">
        <Link href="/">Home</Link> / <Link href="/shop">Shop</Link> / {product.category?.name} / {product.name}
      </p>
      <div className="mt-6 grid gap-8 lg:grid-cols-[1fr_1fr]">
        <div className="grid grid-cols-[72px_1fr] gap-3 md:grid-cols-[120px_1fr]">
          <div className="space-y-3">
            {product.images.map((item, index) => (
              <button key={item.id} onClick={() => setImage(index)} className={`overflow-hidden rounded-[16px] border ${image === index ? "border-black" : "border-transparent"}`}>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={item.url} alt="" className="aspect-square w-full object-cover" />
              </button>
            ))}
          </div>
          <div className="overflow-hidden rounded-[20px] bg-muted">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={product.images[image]?.url || product.image || ""} alt={product.name} className="aspect-square w-full object-cover" />
          </div>
        </div>
        <div>
          <h1 className="font-display text-4xl leading-none md:text-5xl">{product.name.toUpperCase()}</h1>
          <div className="mt-3">
            <Stars value={product.rating} />
          </div>
          <p className="mt-4 flex items-center gap-3 text-3xl font-bold">
            {money(product.effective_price)}
            {off ? (
              <>
                <span className="text-2xl text-black/30 line-through">{money(product.price)}</span>
                <span className="rounded-full bg-sale/10 px-3 py-1 text-sm text-sale">-{off}%</span>
              </>
            ) : null}
          </p>
          <p className="mt-4 max-w-xl text-sm leading-6 text-black/60">{product.description}</p>
          <p className="mt-4 text-sm">{product.in_stock ? `${product.stock} in stock` : "Out of stock"}</p>
          <div className="mt-6 border-t border-line pt-5">
            <p className="text-sm text-black/50">Choose Size</p>
            <div className="mt-3 flex flex-wrap gap-2">
              {product.sizes.map((item) => (
                <button key={item} onClick={() => setSize(item)} className={`rounded-full px-5 py-2 text-sm ${size === item ? "bg-black text-white" : "bg-muted"}`}>
                  {item}
                </button>
              ))}
            </div>
          </div>
          <div className="mt-6 flex flex-col gap-3 sm:flex-row">
            <div className="flex items-center justify-between rounded-full bg-muted px-5 py-3 sm:w-40">
              <button onClick={() => setQty((value) => Math.max(1, value - 1))} aria-label="Decrease">−</button>
              <span>{qty}</span>
              <button onClick={() => setQty((value) => value + 1)} aria-label="Increase">+</button>
            </div>
            <button disabled={!product.in_stock} onClick={addToCart} className="flex-1 rounded-full bg-black py-3 text-white disabled:opacity-40">
              {product.in_stock ? "Add to Cart" : "Out of Stock"}
            </button>
          </div>
          {message ? <p className="mt-3 text-sm text-sale">{message}</p> : null}
        </div>
      </div>
      <section className="mt-16">
        <h2 className="font-display text-4xl">YOU MIGHT ALSO LIKE</h2>
        <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-4">
          {related.map((item) => (
            <ProductCard key={item.id} product={item} />
          ))}
        </div>
      </section>
    </main>
  );
}
