"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function ManualGameForm({ onClose }: { onClose: () => void }) {
  const router = useRouter();
  const [form, setForm] = useState({
    name: "",
    image: "",
    description: "",
    minPlayers: "",
    maxPlayers: "",
    playingTime: "",
    howToPlayUrl: "",
  });
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  function update<K extends keyof typeof form>(key: K, value: string) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const res = await fetch("/api/games/manual", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: form.name,
          image: form.image || undefined,
          description: form.description || undefined,
          minPlayers: form.minPlayers ? Number(form.minPlayers) : undefined,
          maxPlayers: form.maxPlayers ? Number(form.maxPlayers) : undefined,
          playingTime: form.playingTime ? Number(form.playingTime) : undefined,
          howToPlayUrl: form.howToPlayUrl || undefined,
        }),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || "Toevoegen mislukt");
      }
      router.refresh();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Toevoegen mislukt");
    } finally {
      setLoading(false);
    }
  }

  const inputClass =
    "w-full rounded bg-background px-3 py-2 text-sm outline-none ring-1 ring-white/10 focus:ring-accent";

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-3">
      <input
        autoFocus
        className={inputClass}
        placeholder="Titel *"
        value={form.name}
        onChange={(e) => update("name", e.target.value)}
        required
      />
      <input
        className={inputClass}
        placeholder="Cover art URL"
        value={form.image}
        onChange={(e) => update("image", e.target.value)}
        type="url"
      />
      <textarea
        className={inputClass}
        placeholder="Beschrijving"
        value={form.description}
        onChange={(e) => update("description", e.target.value)}
        rows={3}
      />
      <div className="grid grid-cols-3 gap-2">
        <input
          className={inputClass}
          placeholder="Min. spelers"
          value={form.minPlayers}
          onChange={(e) => update("minPlayers", e.target.value)}
          type="number"
          min={1}
        />
        <input
          className={inputClass}
          placeholder="Max. spelers"
          value={form.maxPlayers}
          onChange={(e) => update("maxPlayers", e.target.value)}
          type="number"
          min={1}
        />
        <input
          className={inputClass}
          placeholder="Speeltijd (min)"
          value={form.playingTime}
          onChange={(e) => update("playingTime", e.target.value)}
          type="number"
          min={1}
        />
      </div>
      <input
        className={inputClass}
        placeholder="How to play video-link (YouTube, etc.)"
        value={form.howToPlayUrl}
        onChange={(e) => update("howToPlayUrl", e.target.value)}
        type="url"
      />
      {error && <p className="text-sm text-red-400">{error}</p>}
      <button
        type="submit"
        disabled={loading || form.name.trim().length === 0}
        className="rounded bg-accent px-3 py-2 text-sm font-semibold hover:bg-accentHover disabled:opacity-50"
      >
        {loading ? "Bezig..." : "Spel toevoegen"}
      </button>
    </form>
  );
}
