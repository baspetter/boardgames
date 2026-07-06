"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

export function CreatePlayGroupForm() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    const res = await fetch("/api/playgroups", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
    setLoading(false);
    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      setError(data.error || "Aanmaken mislukt");
      return;
    }
    setName("");
    router.refresh();
  }

  return (
    <form onSubmit={handleSubmit} className="flex gap-2">
      <input
        className="flex-1 rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
        placeholder="Naam nieuwe playgroup"
        value={name}
        onChange={(e) => setName(e.target.value)}
        required
        minLength={2}
      />
      <button
        disabled={loading}
        className="rounded bg-accent px-4 py-2 font-semibold hover:bg-accentHover disabled:opacity-50"
      >
        {loading ? "Bezig..." : "Aanmaken"}
      </button>
      {error && <p className="self-center text-sm text-red-400">{error}</p>}
    </form>
  );
}

export function JoinPlayGroupForm() {
  const router = useRouter();
  const [code, setCode] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    const res = await fetch("/api/playgroups/join", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ inviteCode: code }),
    });
    setLoading(false);
    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      setError(data.error || "Joinen mislukt");
      return;
    }
    setCode("");
    router.refresh();
  }

  return (
    <form onSubmit={handleSubmit} className="flex gap-2">
      <input
        className="flex-1 rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
        placeholder="Uitnodigingscode van een groep"
        value={code}
        onChange={(e) => setCode(e.target.value)}
        required
      />
      <button
        disabled={loading}
        className="rounded bg-surfaceHover px-4 py-2 font-semibold hover:bg-white/20 disabled:opacity-50"
      >
        {loading ? "Bezig..." : "Join"}
      </button>
      {error && <p className="self-center text-sm text-red-400">{error}</p>}
    </form>
  );
}
