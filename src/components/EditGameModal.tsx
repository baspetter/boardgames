"use client";

import { useRouter } from "next/navigation";
import GameForm, { GameFormValues } from "@/components/GameForm";

export interface EditableGame {
  id: string;
  name: string;
  image: string | null;
  description: string | null;
  yearPublished: number | null;
  minPlayers: number | null;
  maxPlayers: number | null;
  bestPlayers: number | null;
  playingTime: number | null;
  weight: number | null;
  howToPlayUrl: string | null;
}

function toFormValues(game: EditableGame): GameFormValues {
  return {
    name: game.name,
    image: game.image ?? "",
    description: game.description ?? "",
    yearPublished: game.yearPublished?.toString() ?? "",
    minPlayers: game.minPlayers?.toString() ?? "",
    maxPlayers: game.maxPlayers?.toString() ?? "",
    bestPlayers: game.bestPlayers?.toString() ?? "",
    playingTime: game.playingTime?.toString() ?? "",
    weight: game.weight?.toString() ?? "",
    gameType: "",
    howToPlayUrl: game.howToPlayUrl ?? "",
  };
}

export default function EditGameModal({ game, onClose }: { game: EditableGame; onClose: () => void }) {
  const router = useRouter();

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/70 p-6 pt-24">
      <div className="w-full max-w-lg rounded-lg border border-white/10 bg-surface p-5 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold">Spel bewerken</h2>
          <button className="text-white/50 hover:text-white" onClick={onClose}>
            ✕
          </button>
        </div>
        <GameForm
          initialValues={toFormValues(game)}
          submitLabel="Opslaan"
          onSubmit={async (payload) => {
            const res = await fetch(`/api/games/${game.id}`, {
              method: "PATCH",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(payload),
            });
            if (!res.ok) {
              const data = await res.json().catch(() => ({}));
              throw new Error(data.error || "Opslaan mislukt");
            }
            router.refresh();
            onClose();
          }}
        />
        <p className="mt-2 text-xs text-white/40">
          Laat "Type spel" leeg om bestaande categorieën/tags ongemoeid te laten.
        </p>
      </div>
    </div>
  );
}
