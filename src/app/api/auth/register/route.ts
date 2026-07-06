import { NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import { z } from "zod";
import { prisma } from "@/lib/prisma";

const registerSchema = z.object({
  email: z.string().email(),
  username: z.string().min(3).max(32),
  password: z.string().min(8),
  inviteCode: z.string().min(1),
});

export async function POST(req: Request) {
  const body = await req.json();
  const parsed = registerSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 });
  }

  const { email, username, password, inviteCode } = parsed.data;

  const code = await prisma.inviteCode.findUnique({ where: { code: inviteCode } });
  if (!code) {
    return NextResponse.json({ error: "Ongeldige uitnodigingscode" }, { status: 400 });
  }
  if (code.expiresAt && code.expiresAt < new Date()) {
    return NextResponse.json({ error: "Uitnodigingscode is verlopen" }, { status: 400 });
  }
  if (code.usesCount >= code.maxUses) {
    return NextResponse.json({ error: "Uitnodigingscode is al gebruikt" }, { status: 400 });
  }

  const existingEmail = await prisma.user.findUnique({ where: { email } });
  if (existingEmail) {
    return NextResponse.json({ error: "E-mailadres is al in gebruik" }, { status: 400 });
  }
  const existingUsername = await prisma.user.findUnique({ where: { username } });
  if (existingUsername) {
    return NextResponse.json({ error: "Gebruikersnaam is al in gebruik" }, { status: 400 });
  }

  const passwordHash = await bcrypt.hash(password, 12);

  await prisma.$transaction([
    prisma.user.create({
      data: { email, username, passwordHash, inviteCodeId: code.id },
    }),
    prisma.inviteCode.update({
      where: { id: code.id },
      data: { usesCount: { increment: 1 } },
    }),
  ]);

  return NextResponse.json({ ok: true });
}
