"use client";

import { Suspense } from "react";
import { AdminShell } from "@/components/admin/Shell";

export default function PanelLayout({ children }: { children: React.ReactNode }) {
  return (
    <AdminShell>
      <Suspense fallback={<p className="text-sm">Loading…</p>}>{children}</Suspense>
    </AdminShell>
  );
}
