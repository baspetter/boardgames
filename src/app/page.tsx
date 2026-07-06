import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import GameGrid from "@/components/GameGrid";
import AddGameButton from "@/components/AddGameButton";

export default async function HomePage() {
  const userId = await requireUserId();

  const entries = await prisma.collectionEntry.findMany({
    where: { userId },
    include: { game: true },
    orderBy: { addedAt: "desc" },
  });

  const games = entries.map((e) => ({
    id: e.game.id,
    name: e.game.name,
    image: e.game.image,
    thumbnail: e.game.thumbnail,
    yearPublished: e.game.yearPublished,
    bggRating: e.game.bggRating,
  }));

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">Mijn collectie</h1>
        <AddGameButton />
      </div>
      <GameGrid
        games={games}
        emptyMessage="Je hebt nog geen spellen toegevoegd. Klik op 'Spel toevoegen' om te starten."
      />
    </div>
  );
}
