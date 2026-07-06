import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { generatePlayGroupCode } from "@/lib/codes";

const createSchema = z.object({
  name: z.string().min(2).max(64),
});

export async function GET() {
  const userId = await requireUserId();

  const groups = await prisma.playGroup.findMany({
    where: { members: { some: { userId } } },
    include: { members: { include: { user: { select: { id: true, username: true } } } } },
    orderBy: { createdAt: "asc" },
  });

  return NextResponse.json(groups);
}

export async function POST(req: Request) {
  const userId = await requireUserId();
  const body = await req.json();
  const parsed = createSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }

  let inviteCode = generatePlayGroupCode();
  while (await prisma.playGroup.findUnique({ where: { inviteCode } })) {
    inviteCode = generatePlayGroupCode();
  }

  const group = await prisma.playGroup.create({
    data: {
      name: parsed.data.name,
      inviteCode,
      createdById: userId,
      members: {
        create: { userId, role: "OWNER" },
      },
    },
    include: { members: { include: { user: { select: { id: true, username: true } } } } },
  });

  return NextResponse.json(group);
}
