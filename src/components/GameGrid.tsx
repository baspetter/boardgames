import GameCard, { GameCardData } from "@/components/GameCard";

export default function GameGrid({
  games,
  emptyMessage = "Nog geen spellen toegevoegd.",
}: {
  games: GameCardData[];
  emptyMessage?: string;
}) {
  if (games.length === 0) {
    return <p className="py-12 text-center text-white/50">{emptyMessage}</p>;
  }

  return (
    <div className="columns-2 gap-4 sm:columns-3 md:columns-4 lg:columns-6">
      {games.map((game) => (
        <GameCard key={game.id} game={game} />
      ))}
    </div>
  );
}
