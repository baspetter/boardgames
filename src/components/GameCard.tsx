import Link from "next/link";

export interface GameCardData {
  id: string;
  name: string;
  image?: string | null;
  thumbnail?: string | null;
  yearPublished?: number | null;
  bggRating?: number | null;
  owners?: { id: string; username: string }[];
}

export default function GameCard({ game }: { game: GameCardData }) {
  const cover = game.image || game.thumbnail;

  return (
    <Link
      href={`/games/${game.id}`}
      className="group relative mb-4 block break-inside-avoid overflow-hidden rounded-md bg-surface transition-transform duration-200 hover:z-10 hover:scale-105 hover:shadow-2xl hover:shadow-black/60"
    >
      <div className="relative w-full bg-surfaceHover">
        {cover ? (
          // Cover art comes in whatever aspect ratio it was uploaded/sourced
          // in (square, landscape, portrait) — a plain <img> keeps that
          // instead of forcing a crop, which is what the masonry grid needs.
          // eslint-disable-next-line @next/next/no-img-element
          <img src={cover} alt={game.name} className="block w-full h-auto" loading="lazy" />
        ) : (
          <div className="flex aspect-[3/4] items-center justify-center text-sm text-white/40">
            Geen afbeelding
          </div>
        )}
        {typeof game.bggRating === "number" && (
          <span className="absolute right-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-xs font-semibold text-accent">
            {game.bggRating.toFixed(1)}
          </span>
        )}
      </div>
      <div className="p-2">
        <p className="truncate text-sm font-medium text-white">{game.name}</p>
        <div className="flex items-center justify-between text-xs text-white/50">
          <span>{game.yearPublished ?? ""}</span>
          {game.owners && game.owners.length > 0 && (
            <span className="truncate">{game.owners.map((o) => o.username).join(", ")}</span>
          )}
        </div>
      </div>
    </Link>
  );
}
