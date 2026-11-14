import { LoginForm } from "@/components/admin/LoginForm";
import { Logo } from "@/components/site/Header";

export const metadata = { title: "Вход" };

export default function LoginPage() {
  return (
    <main className="relative isolate grid min-h-dvh place-items-center px-5">
      <div aria-hidden className="absolute top-1/4 left-1/2 -z-10 h-80 w-80 -translate-x-1/2 rounded-full bg-accent-600/20 blur-[100px]" />
      <div className="w-full max-w-sm">
        <div className="mb-8 flex justify-center">
          <Logo />
        </div>
        <LoginForm />
      </div>
    </main>
  );
}
