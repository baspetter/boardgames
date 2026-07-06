import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";

export async function GET(_req: Request, { params }: { params: Promise<{ id: string }> }) {
  const userId = await requireUserId();
  const { id } = await params;

  const membership = await prisma.playGroupMember.findUnique({
    where: { userId_playGroupId: { userId, playGroupId: id } },
  });
  if (!membership) {
    return NextResponse.json({ error: "Geen toegang tot deze groep" }, { status: 403 });
  }

  const group = await prisma.playGroup.findUnique({
    where: { id },
    include: { members: { include: { user: { select: { id: true, username: true } } } } },
  });
  if (!group) {
    return NextResponse.json({ error: "Groep niet gevonden" }, { status: 404 });
  }

  return NextResponse.json(group);
}
