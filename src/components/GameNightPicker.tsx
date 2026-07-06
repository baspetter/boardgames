"use client";

import { useMemo, useState } from "react";
import GameGrid from "@/components/GameGrid";
import { GameCardData } from "@/components/GameCard";

interface Member {
  id: string;
  username: string;
}

interface Group {
  id: string;
  name: string;
  members: Member[];
}

export default function GameNightPicker({ groups }: { groups: Group[] }) {
  const [groupId, setGroupId] = useState(groups[0]?.id ?? "");
  const group = useMemo(() => groups.find((g) => g.id === groupId), [groups, groupId]);
  const [present, setPresent] = useState<Set<string>>(new Set(group?.members.map((m) => m.id)));
  const [loading, setLoading] = useState(false);
  const [games, setGames] = useState<GameCardData[] | null>(null);

  function selectGroup(id: string) {
    setGroupId(id);
    const g = groups.find((x) => x.id === id);
    setPresent(new Set(g?.members.map((m) => m.id)));
    setGames(null);
  }

  function togglePresent(id: string) {
    setPresent((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }

  async function findGames() {
    if (!group || present.size === 0) return;
    setLoading(true);
    try {
      const res = await fetch("/api/game-night", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ playGroupId: group.id, presentUserIds: Array.from(present) }),
      });
      const data = await res.json();
      const mapped: GameCardData[] = data.suggestions.map((s: any) => ({
        id: s.game.id,
        name: s.game.name,
        image: s.game.image,
        thumbnail: s.game.thumbnail,
        yearPublished: s.game.yearPublished,
        bggRating: s.game.bggRating,
        owners: s.owners,
      }));
      setGames(mapped);
    } finally {
      setLoading(false);
    }
  }

  if (groups.length === 0) {
    return <p className="text-white/50">Je bent nog geen lid van een playgroup.</p>;
  }

  return (
    <div>
      <div className="mb-4 flex flex-wrap gap-2">
        {groups.map((g) => (
          <button
            key={g.id}
            onClick={() => selectGroup(g.id)}
            className={`rounded-full px-4 py-1.5 text-sm ${
              g.id === groupId ? "bg-accent font-semibold" : "bg-surface hover:bg-surfaceHover"
            }`}
          >
            {g.name}
          </button>
        ))}
      </div>

      {group && (
        <div className="mb-6 rounded-lg border border-white/10 bg-surface p-4">
          <p className="mb-2 text-sm font-semibold text-white/70">Wie is er vanavond bij?</p>
          <div className="flex flex-wrap gap-2">
            {group.members.map((m) => (
              <button
                key={m.id}
                onClick={() => togglePresent(m.id)}
                className={`rounded-full px-3 py-1 text-sm ${
                  present.has(m.id) ? "bg-accent" : "bg-surfaceHover text-white/50"
                }`}
              >
                {m.username}
              </button>
            ))}
          </div>
          <button
            onClick={findGames}
            disabled={loading || present.size === 0}
            className="mt-4 rounded bg-accent px-4 py-2 font-semibold hover:bg-accentHover disabled:opacity-50"
          >
            {loading ? "Zoeken..." : `Zoek spellen voor ${present.size} spelers`}
          </button>
        </div>
      )}

      {games !== null && (
        <GameGrid games={games} emptyMessage="Geen geschikte spellen gevonden voor dit aantal spelers." />
      )}
    </div>
  );
}
