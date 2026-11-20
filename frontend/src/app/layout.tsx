import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "SHOP.CO",
  description: "Find clothes that match your style.",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body className="antialiased">{children}</body>
    </html>
  );
}
