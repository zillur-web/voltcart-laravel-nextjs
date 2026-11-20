export type Category = {
  id: number;
  parent_id: number | null;
  name: string;
  slug: string;
  image: string | null;
  is_active: boolean;
  sort_order: number;
  children?: Category[];
};

export type Product = {
  id: number;
  name: string;
  slug: string;
  description?: string;
  price: string;
  discount_price: string | null;
  effective_price: string;
  rating: number;
  reviews_count: number;
  stock: number;
  in_stock: boolean;
  color: string | null;
  sizes: string[];
  sku?: string;
  is_active?: boolean;
  category_id?: number;
  category: { id: number; name: string; slug: string } | null;
  image: string | null;
  images: { id: number; url: string; is_primary: boolean }[];
};

export type CartLine = {
  id: number;
  quantity: number;
  size: string | null;
  unit_price: string;
  line_total: string;
  product: Product;
};

export type Cart = {
  cart_token: string | null;
  items: CartLine[];
  summary: { subtotal: string; discount: string; shipping: string; total: string; currency: string };
  coupon: { code: string; type: string; value: string } | null;
  coupon_error: string | null;
};

export type Order = {
  id: number;
  number: string;
  status: string;
  email: string;
  subtotal: string;
  discount: string;
  shipping: string;
  total: string;
  coupon_code: string | null;
  created_at: string;
  paid_at: string | null;
  items: {
    id: number;
    product_id: number | null;
    name: string;
    sku: string;
    unit_price: string;
    quantity: number;
    line_total: string;
  }[];
  shipping_address?: {
    name: string;
    phone: string;
    line1: string;
    line2: string | null;
    city: string;
    state: string;
    postal_code: string;
    country: string;
  };
  customer?: { id?: number; name: string; email: string };
  payments?: { id: number; status: string; amount: string; currency: string }[];
  allowed_transitions?: string[];
};

export type StaffUser = {
  id: number;
  name: string;
  email: string;
  roles: { id: number; name: string; slug: string; is_super: boolean }[];
  permissions: string[];
  is_super_admin: boolean;
};
