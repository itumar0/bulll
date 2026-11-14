import Link from "next/link";
import type { AnchorHTMLAttributes, ButtonHTMLAttributes, ReactNode } from "react";

type Variant = "primary" | "outline" | "ghost" | "whatsapp";
type Size = "md" | "lg" | "sm";

const variants: Record<Variant, string> = {
  primary:
    "bg-accent-600 text-white hover:bg-accent-700 shadow-glow hover:shadow-[0_0_0_1px_rgb(34_197_94/0.5),0_14px_50px_-10px_rgb(34_197_94/0.7)]",
  outline: "border border-white/15 text-white hover:border-white/40 hover:bg-white/5",
  ghost: "text-soft hover:text-white hover:bg-white/5",
  whatsapp: "border border-white/15 text-white hover:border-[#25D366]/70 hover:bg-[#25D366]/10",
};
const sizes: Record<Size, string> = {
  sm: "h-9 px-3.5 text-sm rounded-lg gap-1.5",
  md: "h-12 px-5 text-[15px] rounded-xl gap-2",
  lg: "h-14 px-7 text-base rounded-2xl gap-2.5",
};

export function buttonClass(variant: Variant = "primary", size: Size = "md", extra = "") {
  return `inline-flex items-center justify-center font-semibold tracking-tight transition-all duration-200 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${extra}`;
}

export function Button({
  variant,
  size,
  className = "",
  ...rest
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant; size?: Size }) {
  return <button className={buttonClass(variant, size, className)} {...rest} />;
}

export function ButtonLink({
  href,
  variant,
  size,
  className = "",
  external,
  children,
  ...rest
}: AnchorHTMLAttributes<HTMLAnchorElement> & {
  href: string;
  variant?: Variant;
  size?: Size;
  external?: boolean;
  children: ReactNode;
}) {
  const cls = buttonClass(variant, size, className);
  if (external) {
    return (
      <a href={href} target="_blank" rel="noopener noreferrer" className={cls} {...rest}>
        {children}
      </a>
    );
  }
  return (
    <Link href={href} className={cls} {...rest}>
      {children}
    </Link>
  );
}
