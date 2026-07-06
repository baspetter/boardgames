import { prisma } from "@/lib/prisma";

export async function getVisibleOwners(gameId: string, userId: string) {
  const memberships = await prisma.playGroupMember.findMany({
    where: { userId },
    select: { playGroupId: true },
  });
  const groupIds = memberships.map((m) => m.playGroupId);

  const peerMemberships = await prisma.playGroupMember.findMany({
    where: { playGroupId: { in: groupIds } },
    select: { userId: true },
  });
  const peerUserIds = Array.from(new Set(peerMemberships.map((m) => m.userId)));

  const entries = await prisma.collectionEntry.findMany({
    where: { gameId, userId: { in: peerUserIds } },
    include: { user: true },
  });

  return entries.map((e) => ({ id: e.user.id, username: e.user.username }));
}

export async function getPlayGroupCollection(playGroupId: string) {
  const members = await prisma.playGroupMember.findMany({
    where: { playGroupId },
    include: { user: true },
  });
  const userIds = members.map((m) => m.userId);

  const entries = await prisma.collectionEntry.findMany({
    where: { userId: { in: userIds } },
    include: { game: true, user: true },
    orderBy: { addedAt: "desc" },
  });

  const byGame = new Map<
    string,
    { game: (typeof entries)[number]["game"]; owners: { id: string; username: string }[] }
  >();

  for (const entry of entries) {
    const existing = byGame.get(entry.game.id);
    if (existing) {
      existing.owners.push({ id: entry.user.id, username: entry.user.username });
    } else {
      byGame.set(entry.game.id, {
        game: entry.game,
        owners: [{ id: entry.user.id, username: entry.user.username }],
      });
    }
  }

  return Array.from(byGame.values());
}
