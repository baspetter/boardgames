"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

export default function AddToCollectionButton({ bggId }: { bggId: number }) {
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  async function handleAdd() {
    setLoading(true);
    try {
      await fetch("/api/collection", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ bggId }),
      });
      router.refresh();
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      onClick={handleAdd}
      disabled={loading}
      className="rounded bg-accent px-4 py-2 font-semibold hover:bg-accentHover disabled:opacity-50"
    >
      {loading ? "Bezig..." : "+ Toevoegen aan mijn collectie"}
    </button>
  );
}
