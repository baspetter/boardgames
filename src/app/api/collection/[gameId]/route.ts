import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";

export async function DELETE(_req: Request, { params }: { params: Promise<{ gameId: string }> }) {
  const userId = await requireUserId();
  const { gameId } = await params;

  await prisma.collectionEntry.deleteMany({
    where: { userId, gameId },
  });

  return NextResponse.json({ ok: true });
}
