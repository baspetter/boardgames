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
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
      {games.map((game) => (
        <GameCard key={game.id} game={game} />
      ))}
    </div>
  );
}
