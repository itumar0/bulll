import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { AdminDashboard } from "@/components/admin/AdminDashboard";
import { SESSION_COOKIE, verifySessionToken } from "@/lib/auth";
import { businessDate } from "@/lib/time";

export const dynamic = "force-dynamic";
export const metadata = { title: "Дашборд" };

export default async function AdminPage() {
  // Defense in depth: middleware already guards /admin.
  const ok = await verifySessionToken((await cookies()).get(SESSION_COOKIE)?.value);
  if (!ok) redirect("/admin/login");
  return <AdminDashboard today={businessDate()} />;
}
