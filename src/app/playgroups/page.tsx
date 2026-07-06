import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { requireUserId } from "@/lib/current-user";
import { CreatePlayGroupForm, JoinPlayGroupForm } from "@/components/PlayGroupForms";

export default async function PlayGroupsPage() {
  const userId = await requireUserId();

  const groups = await prisma.playGroup.findMany({
    where: { members: { some: { userId } } },
    include: { members: true },
    orderBy: { createdAt: "asc" },
  });

  return (
    <div className="max-w-2xl">
      <h1 className="mb-6 text-2xl font-bold">Playgroups</h1>

      <div className="mb-8 space-y-3 rounded-lg border border-white/10 bg-surface p-4">
        <h2 className="font-semibold">Nieuwe groep aanmaken</h2>
        <CreatePlayGroupForm />
        <div className="my-2 border-t border-white/10" />
        <h2 className="font-semibold">Lid worden van een groep</h2>
        <JoinPlayGroupForm />
      </div>

      <div className="space-y-2">
        {groups.map((group) => (
          <Link
            key={group.id}
            href={`/playgroups/${group.id}`}
            className="flex items-center justify-between rounded-lg bg-surface p-4 hover:bg-surfaceHover"
          >
            <div>
              <p className="font-medium">{group.name}</p>
              <p className="text-sm text-white/50">{group.members.length} leden</p>
            </div>
            <span className="text-white/40">→</span>
          </Link>
        ))}
        {groups.length === 0 && (
          <p className="text-white/50">Je bent nog geen lid van een playgroup.</p>
        )}
      </div>
    </div>
  );
}
