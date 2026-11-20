"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { ProductCard } from "@/components/shop/ProductCard";
import { api } from "@/lib/api";
import type { Category, Product } from "@/lib/types";

const brands = ["VERSACE", "ZARA", "GUCCI", "PRADA", "Calvin Klein"];

export default function HomePage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [arrivals, setArrivals] = useState<Product[]>([]);
  const [selling, setSelling] = useState<Product[]>([]);

  useEffect(() => {
    api<{ categories: Category[]; new_arrivals: Product[]; top_selling: Product[] }>("/home", { auth: false }).then((response) => {
      setCategories(response.data.categories);
      setArrivals(response.data.new_arrivals.slice(0, 4));
      setSelling(response.data.top_selling);
    });
  }, []);

  return (
    <main>
      <section className="bg-soft">
        <div className="mx-auto grid max-w-[1440px] items-center lg:grid-cols-2">
          <div className="rise px-5 py-10 md:px-16 md:py-16">
            <h1 className="font-display text-[40px] leading-[0.95] md:text-[64px]">FIND CLOTHES THAT MATCHES YOUR STYLE</h1>
            <p className="mt-5 max-w-md text-sm text-black/60 md:text-base">
              Browse through our diverse range of meticulously crafted garments, designed to bring out your individuality and cater to your sense of style.
            </p>
            <Link href="/shop" className="mt-6 inline-flex rounded-full bg-black px-16 py-4 text-sm text-white">
              Shop Now
            </Link>
            <dl className="mt-8 grid grid-cols-3 gap-4 border-t border-black/10 pt-6 text-center md:text-left">
              {[
                ["200+", "International Brands"],
                ["2,000+", "High-Quality Products"],
                ["30,000+", "Happy Customers"],
              ].map(([stat, label]) => (
                <div key={label} className="border-black/10 md:border-l md:pl-6 md:first:border-0 md:first:pl-0">
                  <dt className="text-2xl font-bold md:text-4xl">{stat}</dt>
                  <dd className="mt-1 text-xs text-black/60 md:text-sm">{label}</dd>
                </div>
              ))}
            </dl>
          </div>
          <div className="rise relative min-h-[420px] overflow-hidden lg:min-h-[640px]">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1400&q=80"
              alt="Shoppers in tailored casual clothes"
              className="h-full w-full object-cover object-top"
            />
          </div>
        </div>
      </section>

      <section id="brands" className="overflow-hidden bg-black text-white">
        <div className="marquee-track flex w-[200%] gap-16 py-8 text-2xl font-bold tracking-[0.2em] md:text-4xl">
          {[...brands, ...brands, ...brands, ...brands].map((brand, index) => (
            <span key={`${brand}-${index}`} className="shrink-0">
              {brand}
            </span>
          ))}
        </div>
      </section>

      <Section title="NEW ARRIVALS" products={arrivals} />
      <div className="mx-auto max-w-[1240px] px-4">
        <hr className="border-line" />
      </div>
      <Section title="TOP SELLING" products={selling} />

      <section className="mx-auto max-w-[1240px] px-4 py-12">
        <div className="rounded-[40px] bg-muted px-6 py-10 md:px-14 md:py-16">
          <h2 className="text-center font-display text-4xl md:text-5xl">BROWSE BY DRESS STYLE</h2>
          <div className="mt-10 grid gap-4 md:grid-cols-3">
            {categories.map((category, index) => (
              <Link
                key={category.id}
                href={`/shop?category=${category.slug}`}
                className={`group relative h-56 overflow-hidden rounded-[20px] bg-white ${index === 0 || index === 3 ? "md:col-span-1" : "md:col-span-2"}`}
              >
                <span className="absolute left-6 top-6 z-10 text-2xl font-bold">{category.name}</span>
                {category.image ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={category.image} alt="" className="shot h-full w-full object-cover" />
                ) : null}
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-[1240px] px-4 py-8">
        <h2 className="font-display text-4xl md:text-5xl">OUR HAPPY CUSTOMERS</h2>
        <div className="mt-8 grid gap-5 md:grid-cols-3">
          {[
            ["Sarah M.", "I'm blown away by the quality and style of the clothes I received from Shop.co."],
            ["Alex K.", "Finding clothes that align with my personal style used to be a challenge until I discovered Shop.co."],
            ["James L.", "The selection of clothes is not only diverse but also on point with the latest fashion trends."],
          ].map(([name, quote]) => (
            <blockquote key={name} className="rounded-[20px] border border-line px-6 py-6">
              <p className="text-star">★★★★★</p>
              <p className="mt-3 font-bold">{name}</p>
              <p className="mt-2 text-sm text-black/60">{quote}</p>
            </blockquote>
          ))}
        </div>
      </section>
    </main>
  );
}

function Section({ title, products }: { title: string; products: Product[] }) {
  return (
    <section className="mx-auto max-w-[1240px] px-4 py-14">
      <h2 className="text-center font-display text-4xl md:text-5xl">{title}</h2>
      <div className="mt-10 grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-6">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
      <div className="mt-10 text-center">
        <Link href="/shop" className="inline-flex rounded-full border border-black/10 px-16 py-3 text-sm">
          View All
        </Link>
      </div>
    </section>
  );
}
