import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { getOrCacheGame } from "@/lib/games";

const addSchema = z.object({
  bggId: z.number().int().positive(),
});

export async function GET() {
  const userId = await requireUserId();

  const entries = await prisma.collectionEntry.findMany({
    where: { userId },
    include: { game: true },
    orderBy: { addedAt: "desc" },
  });

  return NextResponse.json(entries);
}

export async function POST(req: Request) {
  const userId = await requireUserId();
  const body = await req.json();
  const parsed = addSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }

  const game = await getOrCacheGame(parsed.data.bggId);

  const entry = await prisma.collectionEntry.upsert({
    where: { userId_gameId: { userId, gameId: game.id } },
    create: { userId, gameId: game.id },
    update: {},
    include: { game: true },
  });

  return NextResponse.json(entry);
}
