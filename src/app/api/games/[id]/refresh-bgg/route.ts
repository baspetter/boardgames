import { NextResponse } from "next/server";
import { requireUserId } from "@/lib/current-user";
import { refreshGameFromBgg } from "@/lib/games";

export async function POST(_req: Request, { params }: { params: Promise<{ id: string }> }) {
  await requireUserId();
  const { id } = await params;

  try {
    const game = await refreshGameFromBgg(id);
    return NextResponse.json(game);
  } catch (err) {
    return NextResponse.json(
      { error: err instanceof Error ? err.message : "Verversen mislukt" },
      { status: 400 }
    );
  }
}
