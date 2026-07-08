import { NextResponse } from "next/server";
import { z } from "zod";
import { requireUserId } from "@/lib/current-user";
import { updateGame } from "@/lib/games";

const editGameSchema = z.object({
  name: z.string().min(1).max(200).optional(),
  image: z.string().url().optional().or(z.literal("")),
  description: z.string().max(5000).optional(),
  yearPublished: z.number().int().min(1000).max(3000).optional(),
  minPlayers: z.number().int().positive().optional(),
  maxPlayers: z.number().int().positive().optional(),
  bestPlayers: z.number().int().positive().optional(),
  playingTime: z.number().int().positive().optional(),
  weight: z.number().min(1).max(5).optional(),
  gameType: z.string().max(50).optional().or(z.literal("")),
  howToPlayUrl: z.string().url().optional().or(z.literal("")),
});

export async function PATCH(req: Request, { params }: { params: Promise<{ id: string }> }) {
  await requireUserId();
  const { id } = await params;

  const body = await req.json();
  const parsed = editGameSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }

  const { name, image, description, yearPublished, minPlayers, maxPlayers, bestPlayers, playingTime, weight, gameType, howToPlayUrl } =
    parsed.data;

  const game = await updateGame(id, {
    name,
    image: image || undefined,
    description: description || undefined,
    yearPublished,
    minPlayers,
    maxPlayers,
    bestPlayers,
    playingTime,
    weight,
    categories: gameType ? [gameType] : undefined,
    howToPlayUrl: howToPlayUrl || undefined,
  });

  return NextResponse.json(game);
}
