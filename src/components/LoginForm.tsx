"use client";

import { signIn } from "next-auth/react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";

export default function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const result = await signIn("credentials", {
      email,
      password,
      redirect: false,
    });

    setLoading(false);

    if (result?.error) {
      setError("Onjuiste inloggegevens.");
      return;
    }

    router.push(searchParams.get("callbackUrl") || "/");
    router.refresh();
  }

  return (
    <div className="mx-auto mt-16 max-w-sm rounded-lg border border-white/10 bg-surface p-8">
      <h1 className="mb-6 text-2xl font-bold">Inloggen</h1>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <input
          className="rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
          type="email"
          placeholder="E-mailadres"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <input
          className="rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
          type="password"
          placeholder="Wachtwoord"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
        {error && <p className="text-sm text-red-400">{error}</p>}
        <button
          className="rounded bg-accent px-3 py-2 font-semibold hover:bg-accentHover disabled:opacity-50"
          type="submit"
          disabled={loading}
        >
          {loading ? "Bezig..." : "Inloggen"}
        </button>
      </form>
      <p className="mt-4 text-sm text-white/60">
        Nog geen account?{" "}
        <Link className="text-accent hover:underline" href="/register">
          Registreer met uitnodigingscode
        </Link>
      </p>
    </div>
  );
}
