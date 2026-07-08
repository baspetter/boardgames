"use client";

import { useRouter } from "next/navigation";
import GameForm from "@/components/GameForm";

export default function ManualGameForm({ onClose }: { onClose: () => void }) {
  const router = useRouter();

  return (
    <GameForm
      submitLabel="Spel toevoegen"
      onSubmit={async (payload) => {
        const res = await fetch("/api/games/manual", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        if (!res.ok) {
          const data = await res.json().catch(() => ({}));
          throw new Error(data.error || "Toevoegen mislukt");
        }
        router.refresh();
        onClose();
      }}
    />
  );
}
