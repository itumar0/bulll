"use client";

import { useRouter } from "next/navigation";
import { useState, type FormEvent } from "react";
import { Button } from "../ui/Button";

export function LoginForm() {
  const router = useRouter();
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    if (!password) return;
    setLoading(true);
    setError(null);
    try {
      const res = await fetch("/api/admin/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password }),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        setError(json.error ?? "Ошибка входа");
        setPassword("");
        return;
      }
      router.replace("/admin");
      router.refresh();
    } catch {
      setError("Нет соединения с сервером");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="card p-6 sm:p-8">
      <h1 className="text-2xl font-extrabold tracking-tight">Вход в админ-панель</h1>
      <p className="mt-1 text-sm text-mute">Только для персонала клуба</p>
      <label htmlFor="password" className="label mt-6">Пароль</label>
      <input
        id="password"
        type="password"
        autoComplete="current-password"
        className="field"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? "login-err" : undefined}
        autoFocus
        maxLength={128}
      />
      {error && (
        <p id="login-err" role="alert" className="mt-3 text-sm text-red-400">
          {error}
        </p>
      )}
      <Button type="submit" className="mt-6 w-full" disabled={loading || !password}>
        {loading ? "Проверяем…" : "Войти"}
      </Button>
    </form>
  );
}
