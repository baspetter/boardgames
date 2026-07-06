"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = useState({ email: "", username: "", password: "", inviteCode: "" });
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const res = await fetch("/api/auth/register", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form),
    });

    setLoading(false);

    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      setError(data.error || "Registreren is mislukt.");
      return;
    }

    router.push("/login");
  }

  return (
    <div className="mx-auto mt-16 max-w-sm rounded-lg border border-white/10 bg-surface p-8">
      <h1 className="mb-6 text-2xl font-bold">Account aanmaken</h1>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <input
          className="rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
          type="email"
          placeholder="E-mailadres"
          value={form.email}
          onChange={(e) => setForm({ ...form, email: e.target.value })}
          required
        />
        <input
          className="rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
          type="text"
          placeholder="Gebruikersnaam"
          value={form.username}
          onChange={(e) => setForm({ ...form, username: e.target.value })}
          minLength={3}
          required
        />
        <input
          className="rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
          type="password"
          placeholder="Wachtwoord (min. 8 tekens)"
          value={form.password}
          onChange={(e) => setForm({ ...form, password: e.target.value })}
          minLength={8}
          required
        />
        <input
          className="rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
          type="text"
          placeholder="Uitnodigingscode"
          value={form.inviteCode}
          onChange={(e) => setForm({ ...form, inviteCode: e.target.value })}
          required
        />
        {error && <p className="text-sm text-red-400">{error}</p>}
        <button
          className="rounded bg-accent px-3 py-2 font-semibold hover:bg-accentHover disabled:opacity-50"
          type="submit"
          disabled={loading}
        >
          {loading ? "Bezig..." : "Registreren"}
        </button>
      </form>
      <p className="mt-4 text-sm text-white/60">
        Al een account?{" "}
        <Link className="text-accent hover:underline" href="/login">
          Inloggen
        </Link>
      </p>
    </div>
  );
}
