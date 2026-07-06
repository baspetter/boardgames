import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { generatePlayGroupCode } from "@/lib/codes";

export async function POST(_req: Request, { params }: { params: Promise<{ id: string }> }) {
  const userId = await requireUserId();
  const { id } = await params;

  const membership = await prisma.playGroupMember.findUnique({
    where: { userId_playGroupId: { userId, playGroupId: id } },
  });
  if (!membership || membership.role === "MEMBER") {
    return NextResponse.json({ error: "Alleen eigenaar/beheerder mag de code wijzigen" }, { status: 403 });
  }

  let inviteCode = generatePlayGroupCode();
  while (await prisma.playGroup.findUnique({ where: { inviteCode } })) {
    inviteCode = generatePlayGroupCode();
  }

  const group = await prisma.playGroup.update({
    where: { id },
    data: { inviteCode },
  });

  return NextResponse.json(group);
}
