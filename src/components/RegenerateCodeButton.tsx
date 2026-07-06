"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

export default function RegenerateCodeButton({ groupId }: { groupId: string }) {
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  async function handleClick() {
    if (!confirm("Weet je zeker dat je een nieuwe code wilt genereren? De oude code werkt dan niet meer.")) {
      return;
    }
    setLoading(true);
    try {
      await fetch(`/api/playgroups/${groupId}/regenerate-code`, { method: "POST" });
      router.refresh();
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      onClick={handleClick}
      disabled={loading}
      className="text-xs text-white/50 underline hover:text-white disabled:opacity-50"
    >
      {loading ? "Bezig..." : "Nieuwe code genereren"}
    </button>
  );
}
