import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import GameNightPicker from "@/components/GameNightPicker";

export default async function GameNightPage() {
  const userId = await requireUserId();

  const groups = await prisma.playGroup.findMany({
    where: { members: { some: { userId } } },
    include: { members: { include: { user: true } } },
    orderBy: { createdAt: "asc" },
  });

  const data = groups.map((g) => ({
    id: g.id,
    name: g.name,
    members: g.members.map((m) => ({ id: m.userId, username: m.user.username })),
  }));

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold">Speelavond</h1>
      <GameNightPicker groups={data} />
    </div>
  );
}
