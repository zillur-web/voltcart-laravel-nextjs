"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ProductForm } from "@/components/admin/ProductForm";
import { can, useStaff } from "@/components/admin/Shell";
import { api } from "@/lib/api";
import type { Product } from "@/lib/types";

export default function EditProductPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const { user } = useStaff();
  const [product, setProduct] = useState<Product | null>(null);

  useEffect(() => {
    api<Product>(`/admin/products/${params.id}`, { auth: "admin" }).then((response) => setProduct(response.data));
  }, [params.id]);

  if (!product) return <p className="text-sm">Loading product…</p>;

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-2xl font-bold">Edit product</h1>
        {can(user, "products.delete") ? (
          <button
            className="text-sm text-sale"
            onClick={async () => {
              if (!confirm("Delete this product?")) return;
              await api(`/admin/products/${product.id}`, { method: "DELETE", auth: "admin" });
              router.push("/admin/products");
            }}
          >
            Delete
          </button>
        ) : null}
      </div>
      {can(user, "products.edit") ? <ProductForm product={product} /> : <p>You can view this product but not edit it.</p>}
      <div className="mt-6 flex gap-3">
        {product.images.map((image) => (
          <div key={image.id} className="text-center">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={image.url} alt="" className="h-20 w-20 object-cover" />
            {can(user, "products.edit") ? (
              <button className="mt-1 text-xs underline" onClick={() => api(`/admin/products/${product.id}/images/${image.id}`, { method: "DELETE", auth: "admin" }).then(() => location.reload())}>
                Remove
              </button>
            ) : null}
          </div>
        ))}
      </div>
    </div>
  );
}
