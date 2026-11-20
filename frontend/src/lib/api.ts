export const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000/api/v1";

export type Pagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

type Options = {
  method?: string;
  body?: unknown;
  form?: FormData;
  auth?: "customer" | "admin" | false;
};

export async function api<T>(path: string, options: Options = {}): Promise<{ data: T; message: string; meta?: Pagination }> {
  const headers: Record<string, string> = { Accept: "application/json" };
  const auth = options.auth === undefined ? "customer" : options.auth;

  if (typeof window !== "undefined") {
    if (auth) {
      const token = localStorage.getItem(auth === "admin" ? "shopco_admin_token" : "shopco_token");
      if (token) headers.Authorization = `Bearer ${token}`;
    }
    const cart = localStorage.getItem("shopco_cart");
    if (cart) headers["X-Cart-Token"] = cart;
  }

  let body: BodyInit | undefined;
  if (options.form) {
    body = options.form;
  } else if (options.body !== undefined) {
    headers["Content-Type"] = "application/json";
    body = JSON.stringify(options.body);
  }

  const response = await fetch(`${API_URL}${path}`, {
    method: options.method || "GET",
    headers,
    body,
  });

  const json = await response.json().catch(() => null);

  if (!response.ok || !json?.success) {
    throw new ApiError(json?.message || "Request failed.", response.status, json?.errors);
  }

  if (json.data?.cart_token && typeof window !== "undefined") {
    localStorage.setItem("shopco_cart", json.data.cart_token);
  }

  return json;
}

export function money(value: string | number) {
  return `$${Number(value).toFixed(2)}`;
}

export function fieldError(error: unknown, field: string) {
  if (error instanceof ApiError) {
    return error.errors?.[field]?.[0];
  }
  return undefined;
}
