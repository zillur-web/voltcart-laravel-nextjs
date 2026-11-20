import { money } from "@/lib/api";
import type { Cart } from "@/lib/types";

export function OrderSummary({
  cart,
  code,
  setCode,
  onApply,
  action,
}: {
  cart: Cart;
  code?: string;
  setCode?: (value: string) => void;
  onApply?: () => void;
  action?: React.ReactNode;
}) {
  return (
    <aside className="h-fit rounded-[20px] border border-line p-6">
      <h2 className="text-xl font-bold">Order Summary</h2>
      <dl className="mt-5 space-y-3 text-sm">
        <div className="flex justify-between">
          <dt className="text-black/60">Subtotal</dt>
          <dd className="font-bold">{money(cart.summary.subtotal)}</dd>
        </div>
        <div className="flex justify-between">
          <dt className="text-black/60">Discount {cart.coupon ? `(${cart.coupon.code})` : ""}</dt>
          <dd className="font-bold text-sale">-{money(cart.summary.discount)}</dd>
        </div>
        <div className="flex justify-between">
          <dt className="text-black/60">Delivery Fee</dt>
          <dd className="font-bold">{money(cart.summary.shipping)}</dd>
        </div>
        <div className="flex justify-between border-t border-line pt-3 text-lg">
          <dt>Total</dt>
          <dd className="font-bold">{money(cart.summary.total)}</dd>
        </div>
      </dl>
      {onApply && setCode ? (
        <div className="mt-5 flex gap-2">
          <input value={code} onChange={(event) => setCode(event.target.value)} placeholder="Add promo code" className="w-full rounded-full bg-muted px-4 py-3 text-sm outline-none" />
          <button onClick={onApply} className="rounded-full bg-black px-5 text-sm text-white">
            Apply
          </button>
        </div>
      ) : null}
      {cart.coupon_error ? <p className="mt-2 text-sm text-sale">{cart.coupon_error}</p> : null}
      {action ? <div className="mt-5">{action}</div> : null}
    </aside>
  );
}
