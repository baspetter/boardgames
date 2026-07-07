import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { getVisibleOwners } from "@/lib/collections";
import { bggArtistUrl, bggDesignerUrl, howToPlayYoutubeUrl } from "@/lib/bgg";
import RemoveGameButton from "@/components/RemoveGameButton";
import AddToCollectionButton from "@/components/AddToCollectionButton";

export default async function GameDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const userId = await requireUserId();
  const { id } = await params;

  const game = await prisma.game.findUnique({ where: { id } });
  if (!game) notFound();

  const [owners, myEntry] = await Promise.all([
    getVisibleOwners(id, userId),
    prisma.collectionEntry.findUnique({ where: { userId_gameId: { userId, gameId: id } } }),
  ]);

  const designers = Array.isArray(game.designers) ? (game.designers as { bggId: number; name: string }[]) : [];
  const artists = Array.isArray(game.artists) ? (game.artists as { bggId: number; name: string }[]) : [];

  return (
    <div className="grid grid-cols-1 gap-8 md:grid-cols-[320px_1fr]">
      <div>
        <div className="relative aspect-[3/4] w-full overflow-hidden rounded-lg bg-surface">
          {game.image ? (
            <Image src={game.image} alt={game.name} fill sizes="320px" className="object-cover" />
          ) : null}
        </div>
        <div className="mt-4 flex flex-col gap-2">
          {myEntry ? (
            <RemoveGameButton gameId={game.id} />
          ) : game.bggId ? (
            <AddToCollectionButton bggId={game.bggId} />
          ) : (
            <AddToCollectionButton gameId={game.id} />
          )}
          <a
            href={game.howToPlayUrl || howToPlayYoutubeUrl(game.name)}
            target="_blank"
            rel="noreferrer"
            className="rounded bg-surfaceHover px-4 py-2 text-center font-semibold hover:bg-white/20"
          >
            ▶ How to play
          </a>
          {game.bggId && (
            <a
              href={`https://boardgamegeek.com/boardgame/${game.bggId}`}
              target="_blank"
              rel="noreferrer"
              className="rounded px-4 py-2 text-center text-sm text-white/50 hover:text-white"
            >
              Bekijk op BoardGameGeek
            </a>
          )}
        </div>
      </div>

      <div>
        <h1 className="text-3xl font-bold">{game.name}</h1>
        <div className="mt-2 flex flex-wrap gap-3 text-sm text-white/60">
          {game.yearPublished && <span>{game.yearPublished}</span>}
          {game.minPlayers && game.maxPlayers && (
            <span>
              {game.minPlayers === game.maxPlayers
                ? `${game.minPlayers} spelers`
                : `${game.minPlayers}–${game.maxPlayers} spelers`}
            </span>
          )}
          {game.playingTime && <span>{game.playingTime} min</span>}
          {game.minAge && <span>{game.minAge}+</span>}
          {game.weight && <span>Complexiteit {game.weight.toFixed(1)}/5</span>}
          {game.bggRating && <span className="font-semibold text-accent">★ {game.bggRating.toFixed(1)}</span>}
        </div>

        {(game.categories.length > 0 || game.mechanics.length > 0) && (
          <div className="mt-4 flex flex-wrap gap-2">
            {[...game.categories, ...game.mechanics].map((tag) => (
              <span key={tag} className="rounded-full bg-surface px-3 py-1 text-xs text-white/70">
                {tag}
              </span>
            ))}
          </div>
        )}

        {game.description && (
          <p className="mt-6 max-w-3xl whitespace-pre-line text-white/80">{game.description}</p>
        )}

        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
          {designers.length > 0 && (
            <div>
              <h2 className="mb-1 text-sm font-semibold uppercase tracking-wide text-white/50">Ontwerpers</h2>
              <ul className="space-y-0.5">
                {designers.map((d) => (
                  <li key={d.bggId}>
                    <a
                      href={bggDesignerUrl(d.bggId)}
                      target="_blank"
                      rel="noreferrer"
                      className="text-accent hover:underline"
                    >
                      {d.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}
          {artists.length > 0 && (
            <div>
              <h2 className="mb-1 text-sm font-semibold uppercase tracking-wide text-white/50">Illustratoren</h2>
              <ul className="space-y-0.5">
                {artists.map((a) => (
                  <li key={a.bggId}>
                    <a
                      href={bggArtistUrl(a.bggId)}
                      target="_blank"
                      rel="noreferrer"
                      className="text-accent hover:underline"
                    >
                      {a.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        {owners.length > 0 && (
          <div className="mt-6">
            <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide text-white/50">In bezit van</h2>
            <div className="flex flex-wrap gap-2">
              {owners.map((o) => (
                <span key={o.id} className="rounded-full bg-surface px-3 py-1 text-sm">
                  {o.username}
                </span>
              ))}
            </div>
          </div>
        )}

        <Link href="/" className="mt-8 inline-block text-sm text-white/50 hover:text-white">
          ← Terug naar collectie
        </Link>
      </div>
    </div>
  );
}
