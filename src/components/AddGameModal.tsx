"use client";

import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { extractBggId } from "@/lib/bgg";
import ManualGameForm from "@/components/ManualGameForm";

interface SearchResult {
  bggId: number;
  name: string;
  yearPublished?: number;
}

export default function AddGameModal({ onClose }: { onClose: () => void }) {
  const router = useRouter();
  const [tab, setTab] = useState<"bgg" | "manual">("bgg");
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [addingId, setAddingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const [linkInput, setLinkInput] = useState("");
  const [linkError, setLinkError] = useState<string | null>(null);
  const [linkLoading, setLinkLoading] = useState(false);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (query.trim().length < 2) {
      setResults([]);
      return;
    }
    setLoading(true);
    debounceRef.current = setTimeout(async () => {
      try {
        const res = await fetch(`/api/games/search?q=${encodeURIComponent(query)}`);
        const data = await res.json();
        setResults(data);
      } finally {
        setLoading(false);
      }
    }, 400);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [query]);

  async function handleAdd(bggId: number) {
    setAddingId(bggId);
    setError(null);
    try {
      const res = await fetch("/api/collection", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ bggId }),
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
      setAddingId(null);
    }
  }

  async function handleAddByLink(e: React.FormEvent) {
    e.preventDefault();
    setLinkError(null);

    const bggId = extractBggId(linkInput);
    if (!bggId) {
      setLinkError("Kon geen BGG-ID herkennen in deze link.");
      return;
    }

    setLinkLoading(true);
    try {
      const res = await fetch("/api/collection", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ bggId }),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || "Toevoegen mislukt");
      }
      router.refresh();
      onClose();
    } catch (err) {
      setLinkError(err instanceof Error ? err.message : "Toevoegen mislukt");
    } finally {
      setLinkLoading(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/70 p-6 pt-24">
      <div className="w-full max-w-lg rounded-lg border border-white/10 bg-surface p-5 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold">Spel toevoegen</h2>
          <button className="text-white/50 hover:text-white" onClick={onClose}>
            ✕
          </button>
        </div>
        <div className="mb-4 flex gap-2 text-sm">
          <button
            onClick={() => setTab("bgg")}
            className={`rounded-full px-3 py-1 ${
              tab === "bgg" ? "bg-accent font-semibold" : "bg-surfaceHover text-white/60"
            }`}
          >
            Via BoardGameGeek
          </button>
          <button
            onClick={() => setTab("manual")}
            className={`rounded-full px-3 py-1 ${
              tab === "manual" ? "bg-accent font-semibold" : "bg-surfaceHover text-white/60"
            }`}
          >
            Handmatig
          </button>
        </div>

        {tab === "bgg" ? (
          <>
            <input
              autoFocus
              className="w-full rounded bg-background px-3 py-2 outline-none ring-1 ring-white/10 focus:ring-accent"
              placeholder="Zoek een bordspel op BoardGameGeek..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
            {error && <p className="mt-2 text-sm text-red-400">{error}</p>}
            <div className="mt-4 max-h-96 space-y-1 overflow-y-auto">
              {loading && <p className="py-4 text-center text-sm text-white/50">Zoeken...</p>}
              {!loading &&
                results.map((r) => (
                  <button
                    key={r.bggId}
                    onClick={() => handleAdd(r.bggId)}
                    disabled={addingId !== null}
                    className="flex w-full items-center justify-between rounded px-3 py-2 text-left hover:bg-surfaceHover disabled:opacity-50"
                  >
                    <span>
                      {r.name} {r.yearPublished && <span className="text-white/40">({r.yearPublished})</span>}
                    </span>
                    <span className="text-xs text-accent">
                      {addingId === r.bggId ? "Bezig..." : "+ Toevoegen"}
                    </span>
                  </button>
                ))}
              {!loading && query.trim().length >= 2 && results.length === 0 && (
                <p className="py-4 text-center text-sm text-white/50">Geen resultaten gevonden.</p>
              )}
            </div>

            <div className="my-4 flex items-center gap-3 text-xs text-white/40">
              <div className="h-px flex-1 bg-white/10" />
              of
              <div className="h-px flex-1 bg-white/10" />
            </div>

            <form onSubmit={handleAddByLink} className="flex gap-2">
              <input
                className="flex-1 rounded bg-background px-3 py-2 text-sm outline-none ring-1 ring-white/10 focus:ring-accent"
                placeholder="Plak een BoardGameGeek-link of ID..."
                value={linkInput}
                onChange={(e) => setLinkInput(e.target.value)}
              />
              <button
                type="submit"
                disabled={linkLoading || linkInput.trim().length === 0}
                className="rounded bg-surfaceHover px-3 py-2 text-sm font-semibold hover:bg-white/20 disabled:opacity-50"
              >
                {linkLoading ? "Bezig..." : "Toevoegen"}
              </button>
            </form>
            {linkError && <p className="mt-2 text-sm text-red-400">{linkError}</p>}
            <p className="mt-1 text-xs text-white/40">
              Bijv. https://boardgamegeek.com/boardgame/13/catan
            </p>
          </>
        ) : (
          <ManualGameForm onClose={onClose} />
        )}
      </div>
    </div>
  );
}
