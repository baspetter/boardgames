"use client";

import { useState } from "react";
import { GAME_TYPES } from "@/lib/game-types";

export interface GameFormValues {
  name: string;
  image: string;
  description: string;
  yearPublished: string;
  minPlayers: string;
  maxPlayers: string;
  bestPlayers: string;
  playingTime: string;
  weight: string;
  gameType: string;
  howToPlayUrl: string;
}

export const emptyGameFormValues: GameFormValues = {
  name: "",
  image: "",
  description: "",
  yearPublished: "",
  minPlayers: "",
  maxPlayers: "",
  bestPlayers: "",
  playingTime: "",
  weight: "",
  gameType: "",
  howToPlayUrl: "",
};

export function gameFormValuesToPayload(form: GameFormValues) {
  return {
    name: form.name,
    image: form.image || undefined,
    description: form.description || undefined,
    yearPublished: form.yearPublished ? Number(form.yearPublished) : undefined,
    minPlayers: form.minPlayers ? Number(form.minPlayers) : undefined,
    maxPlayers: form.maxPlayers ? Number(form.maxPlayers) : undefined,
    bestPlayers: form.bestPlayers ? Number(form.bestPlayers) : undefined,
    playingTime: form.playingTime ? Number(form.playingTime) : undefined,
    weight: form.weight ? Number(form.weight) : undefined,
    gameType: form.gameType || undefined,
    howToPlayUrl: form.howToPlayUrl || undefined,
  };
}

export type GameFormPayload = ReturnType<typeof gameFormValuesToPayload>;

export default function GameForm({
  initialValues = emptyGameFormValues,
  submitLabel,
  onSubmit,
}: {
  initialValues?: GameFormValues;
  submitLabel: string;
  onSubmit: (payload: GameFormPayload) => Promise<void>;
}) {
  const [form, setForm] = useState<GameFormValues>(initialValues);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  function update<K extends keyof GameFormValues>(key: K, value: string) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await onSubmit(gameFormValuesToPayload(form));
    } catch (err) {
      setError(err instanceof Error ? err.message : "Opslaan mislukt");
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
      <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
        <input
          className={inputClass}
          placeholder="Jaartal"
          value={form.yearPublished}
          onChange={(e) => update("yearPublished", e.target.value)}
          type="number"
          min={1000}
          max={3000}
        />
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
          placeholder="Beste aantal"
          value={form.bestPlayers}
          onChange={(e) => update("bestPlayers", e.target.value)}
          type="number"
          min={1}
        />
      </div>
      <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
        <input
          className={inputClass}
          placeholder="Speeltijd (min)"
          value={form.playingTime}
          onChange={(e) => update("playingTime", e.target.value)}
          type="number"
          min={1}
        />
        <input
          className={inputClass}
          placeholder="Complexiteit (1-5)"
          value={form.weight}
          onChange={(e) => update("weight", e.target.value)}
          type="number"
          min={1}
          max={5}
          step={0.1}
        />
        <select
          className={inputClass}
          value={form.gameType}
          onChange={(e) => update("gameType", e.target.value)}
        >
          <option value="">Type spel...</option>
          {GAME_TYPES.map((t) => (
            <option key={t} value={t}>
              {t}
            </option>
          ))}
        </select>
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
        {loading ? "Bezig..." : submitLabel}
      </button>
    </form>
  );
}
