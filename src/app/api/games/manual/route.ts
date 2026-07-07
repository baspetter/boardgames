import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { createManualGame } from "@/lib/games";

const manualGameSchema = z.object({
  name: z.string().min(1).max(200),
  image: z.string().url().optional().or(z.literal("")),
  description: z.string().max(5000).optional(),
  minPlayers: z.number().int().positive().optional(),
  maxPlayers: z.number().int().positive().optional(),
  playingTime: z.number().int().positive().optional(),
  howToPlayUrl: z.string().url().optional().or(z.literal("")),
});

export async function POST(req: Request) {
  const userId = await requireUserId();
  const body = await req.json();
  const parsed = manualGameSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }

  const { name, image, description, minPlayers, maxPlayers, playingTime, howToPlayUrl } =
    parsed.data;

  const game = await createManualGame({
    name,
    image: image || undefined,
    description: description || undefined,
    minPlayers,
    maxPlayers,
    playingTime,
    howToPlayUrl: howToPlayUrl || undefined,
  });

  const entry = await prisma.collectionEntry.create({
    data: { userId, gameId: game.id },
    include: { game: true },
  });

  return NextResponse.json(entry);
}
