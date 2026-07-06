import { NextResponse } from "next/server";
import { requireUserId } from "@/lib/current-user";
import { searchGames } from "@/lib/bgg";

export async function GET(req: Request) {
  await requireUserId();

  const { searchParams } = new URL(req.url);
  const query = searchParams.get("q")?.trim();
  if (!query || query.length < 2) {
    return NextResponse.json([]);
  }

  const results = await searchGames(query);
  // Most relevant / recent games first, capped so the dropdown stays usable.
  const sorted = results
    .sort((a, b) => (b.yearPublished ?? 0) - (a.yearPublished ?? 0))
    .slice(0, 20);

  return NextResponse.json(sorted);
}
