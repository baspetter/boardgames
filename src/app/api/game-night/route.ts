import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";

const schema = z.object({
  playGroupId: z.string().min(1),
  presentUserIds: z.array(z.string().min(1)).min(1),
});

export async function POST(req: Request) {
  const userId = await requireUserId();
  const body = await req.json();
  const parsed = schema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }
  const { playGroupId, presentUserIds } = parsed.data;

  const membership = await prisma.playGroupMember.findUnique({
    where: { userId_playGroupId: { userId, playGroupId } },
  });
  if (!membership) {
    return NextResponse.json({ error: "Geen toegang tot deze groep" }, { status: 403 });
  }

  const groupMembers = await prisma.playGroupMember.findMany({
    where: { playGroupId },
    select: { userId: true },
  });
  const groupMemberIds = new Set(groupMembers.map((m) => m.userId));
  const validPresentIds = presentUserIds.filter((id) => groupMemberIds.has(id));
  const playerCount = validPresentIds.length;

  const entries = await prisma.collectionEntry.findMany({
    where: { userId: { in: validPresentIds } },
    include: { game: true, user: true },
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

  const suggestions = Array.from(byGame.values())
    .filter(({ game }) => {
      const min = game.minPlayers ?? 1;
      const max = game.maxPlayers ?? Infinity;
      return playerCount >= min && playerCount <= max;
    })
    .sort((a, b) => (b.game.bggRating ?? 0) - (a.game.bggRating ?? 0));

  return NextResponse.json({ playerCount, suggestions });
}
