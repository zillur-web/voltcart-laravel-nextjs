"use client";

import { ProductForm } from "@/components/admin/ProductForm";

export default function NewProductPage() {
  return (
    <div>
      <h1 className="mb-4 text-2xl font-bold">New product</h1>
      <ProductForm />
    </div>
  );
}
