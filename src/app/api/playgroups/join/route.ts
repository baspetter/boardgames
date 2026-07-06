import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";

const joinSchema = z.object({
  inviteCode: z.string().min(1),
});

export async function POST(req: Request) {
  const userId = await requireUserId();
  const body = await req.json();
  const parsed = joinSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }

  const group = await prisma.playGroup.findUnique({
    where: { inviteCode: parsed.data.inviteCode.toUpperCase() },
  });
  if (!group) {
    return NextResponse.json({ error: "Ongeldige groepscode" }, { status: 404 });
  }

  const existing = await prisma.playGroupMember.findUnique({
    where: { userId_playGroupId: { userId, playGroupId: group.id } },
  });
  if (existing) {
    return NextResponse.json({ error: "Je bent al lid van deze groep" }, { status: 400 });
  }

  await prisma.playGroupMember.create({
    data: { userId, playGroupId: group.id, role: "MEMBER" },
  });

  return NextResponse.json(group);
}
