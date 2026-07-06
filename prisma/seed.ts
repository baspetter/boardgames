import { PrismaClient } from "@prisma/client";
import { randomBytes } from "crypto";

const prisma = new PrismaClient();

function generateCode(): string {
  return randomBytes(4).toString("hex").toUpperCase();
}

async function main() {
  const existing = await prisma.inviteCode.findFirst();
  if (existing) {
    console.log(`Invite code already exists: ${existing.code}`);
    return;
  }

  const code = process.env.SEED_INVITE_CODE || generateCode();
  const inviteCode = await prisma.inviteCode.create({
    data: {
      code,
      maxUses: Number(process.env.SEED_INVITE_MAX_USES || 20),
    },
  });

  console.log(`Created invite code: ${inviteCode.code}`);
  console.log("Share this code with friends so they can register.");
}

main()
  .catch((err) => {
    console.error(err);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
