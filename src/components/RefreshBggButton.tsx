"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

export default function RefreshBggButton({ gameId }: { gameId: string }) {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleRefresh() {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(`/api/games/${gameId}/refresh-bgg`, { method: "POST" });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || "Verversen mislukt");
      }
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Verversen mislukt");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <button
        onClick={handleRefresh}
        disabled={loading}
        className="w-full rounded bg-surfaceHover px-4 py-2 text-center font-semibold hover:bg-white/20 disabled:opacity-50"
      >
        {loading ? "Bezig..." : "↻ Update with BGG"}
      </button>
      {error && <p className="mt-1 text-xs text-red-400">{error}</p>}
    </div>
  );
}
