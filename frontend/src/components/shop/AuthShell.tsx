export function AuthShell({ title, message, children }: { title: string; message?: string; children: React.ReactNode }) {
  return (
    <main className="mx-auto max-w-md px-4 py-16">
      <p className="font-display text-3xl">SHOP.CO</p>
      <h1 className="mt-6 text-3xl font-bold">{title}</h1>
      {message ? <p className="mt-3 text-sm text-sale">{message}</p> : null}
      <div className="mt-6">{children}</div>
    </main>
  );
}
