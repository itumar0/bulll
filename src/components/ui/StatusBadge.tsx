import type { TableState } from "@/lib/types";

const styles: Record<TableState, { dot: string; text: string; ring: string }> = {
  free: { dot: "bg-emerald-400", text: "text-emerald-300", ring: "bg-emerald-400/10 border-emerald-400/20" },
  busy: { dot: "bg-red-500", text: "text-red-300", ring: "bg-red-500/10 border-red-500/25" },
  soon: { dot: "bg-amber-400", text: "text-amber-200", ring: "bg-amber-400/10 border-amber-400/25" },
};

export function StatusBadge({ state, label }: { state: TableState; label: string }) {
  const s = styles[state];
  return (
    <span className={`inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold ${s.ring} ${s.text}`}>
      <span className={`h-2 w-2 rounded-full ${s.dot} ${state !== "free" ? "animate-pulse-dot" : ""}`} aria-hidden />
      {label}
    </span>
  );
}
