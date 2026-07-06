import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { getPlayGroupCollection } from "@/lib/collections";
import GameGrid from "@/components/GameGrid";
import RegenerateCodeButton from "@/components/RegenerateCodeButton";

export default async function PlayGroupDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const userId = await requireUserId();
  const { id } = await params;

  const membership = await prisma.playGroupMember.findUnique({
    where: { userId_playGroupId: { userId, playGroupId: id } },
  });
  if (!membership) notFound();

  const group = await prisma.playGroup.findUnique({
    where: { id },
    include: { members: { include: { user: true } } },
  });
  if (!group) notFound();

  const collection = await getPlayGroupCollection(id);
  const games = collection.map(({ game, owners }) => ({
    id: game.id,
    name: game.name,
    image: game.image,
    thumbnail: game.thumbnail,
    yearPublished: game.yearPublished,
    bggRating: game.bggRating,
    owners,
  }));

  const canManage = membership.role === "OWNER" || membership.role === "ADMIN";

  return (
    <div>
      <div className="mb-2 flex items-center justify-between">
        <h1 className="text-2xl font-bold">{group.name}</h1>
      </div>

      <div className="mb-8 flex flex-wrap items-center gap-4 rounded-lg border border-white/10 bg-surface p-4">
        <div>
          <p className="text-xs uppercase tracking-wide text-white/50">Uitnodigingscode</p>
          <p className="font-mono text-lg tracking-wider text-accent">{group.inviteCode}</p>
        </div>
        {canManage && <RegenerateCodeButton groupId={group.id} />}
        <div className="ml-auto flex flex-wrap gap-2">
          {group.members.map((m) => (
            <span key={m.id} className="rounded-full bg-surfaceHover px-3 py-1 text-sm">
              {m.user.username}
              {m.role !== "MEMBER" && <span className="ml-1 text-white/40">({m.role.toLowerCase()})</span>}
            </span>
          ))}
        </div>
      </div>

      <h2 className="mb-4 text-lg font-semibold">Gezamenlijke collectie</h2>
      <GameGrid games={games} emptyMessage="Niemand in deze groep heeft nog spellen toegevoegd." />
    </div>
  );
}
