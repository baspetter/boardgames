"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

export default function RemoveGameButton({ gameId }: { gameId: string }) {
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  async function handleRemove() {
    setLoading(true);
    try {
      await fetch(`/api/collection/${gameId}`, { method: "DELETE" });
      router.refresh();
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      onClick={handleRemove}
      disabled={loading}
      className="rounded bg-surfaceHover px-4 py-2 font-semibold text-white/80 hover:bg-white/20 disabled:opacity-50"
    >
      {loading ? "Bezig..." : "Verwijder uit mijn collectie"}
    </button>
  );
}
