import { XMLParser } from "fast-xml-parser";

const BGG_BASE = "https://boardgamegeek.com/xmlapi2";

const parser = new XMLParser({
  ignoreAttributes: false,
  attributeNamePrefix: "",
  textNodeName: "text",
});

function asArray<T>(value: T | T[] | undefined | null): T[] {
  if (value === undefined || value === null) return [];
  return Array.isArray(value) ? value : [value];
}

async function fetchWithRetry(url: string, attempts = 5, delayMs = 1500): Promise<string> {
  for (let i = 0; i < attempts; i++) {
    const res = await fetch(url, {
      headers: {
        Accept: "application/xml,text/xml,*/*",
        "Accept-Language": "en-US,en;q=0.9",
        // BGG sits behind Cloudflare, which blocks requests that look
        // scripted (a custom/non-browser User-Agent is enough to get a
        // 401/403). A common desktop browser UA avoids that.
        "User-Agent":
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
      },
    });
    if (res.status === 202 || res.status === 401 || res.status === 403 || res.status === 429) {
      // BGG queued the request (202), or Cloudflare/rate-limiting briefly
      // blocked it (401/403/429) — both are worth a short backoff+retry.
      await new Promise((r) => setTimeout(r, delayMs));
      continue;
    }
    if (!res.ok) {
      throw new Error(`BGG request failed (${res.status}): ${url}`);
    }
    return res.text();
  }
  throw new Error(`BGG request timed out after ${attempts} attempts: ${url}`);
}

export interface BggSearchResult {
  bggId: number;
  name: string;
  yearPublished?: number;
}

export async function searchGames(query: string): Promise<BggSearchResult[]> {
  const url = `${BGG_BASE}/search?type=boardgame&query=${encodeURIComponent(query)}`;
  const xml = await fetchWithRetry(url);
  const parsed = parser.parse(xml);
  const items = asArray<any>(parsed?.items?.item);

  return items
    .map((item) => {
      const names = asArray<any>(item.name);
      const primary = names.find((n) => n.type === "primary") ?? names[0];
      const yearRaw = item.yearpublished?.value;
      return {
        bggId: Number(item.id),
        name: primary?.value ?? "Unknown",
        yearPublished: yearRaw ? Number(yearRaw) : undefined,
      };
    })
    .filter((r) => !Number.isNaN(r.bggId));
}

export interface BggLinkRef {
  bggId: number;
  name: string;
}

export interface BggGameDetails {
  bggId: number;
  name: string;
  yearPublished?: number;
  image?: string;
  thumbnail?: string;
  description?: string;
  minPlayers?: number;
  maxPlayers?: number;
  playingTime?: number;
  minPlayTime?: number;
  maxPlayTime?: number;
  minAge?: number;
  weight?: number;
  bggRating?: number;
  bggRank?: number;
  categories: string[];
  mechanics: string[];
  designers: BggLinkRef[];
  artists: BggLinkRef[];
}

function decodeHtmlEntities(text: string): string {
  return text
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&quot;/g, '"')
    .replace(/&#10;/g, "\n")
    .replace(/&#039;/g, "'");
}

export async function getGameDetails(bggId: number): Promise<BggGameDetails> {
  const url = `${BGG_BASE}/thing?id=${bggId}&stats=1`;
  const xml = await fetchWithRetry(url);
  const parsed = parser.parse(xml);
  const item = asArray<any>(parsed?.items?.item)[0];
  if (!item) {
    throw new Error(`Game not found on BGG: ${bggId}`);
  }

  const names = asArray<any>(item.name);
  const primaryName = names.find((n) => n.type === "primary") ?? names[0];

  const links = asArray<any>(item.link);
  const byType = (type: string) =>
    links.filter((l) => l.type === type).map((l) => ({ bggId: Number(l.id), name: l.value }));

  const categories = links.filter((l) => l.type === "boardgamecategory").map((l) => l.value);
  const mechanics = links.filter((l) => l.type === "boardgamemechanic").map((l) => l.value);
  const designers = byType("boardgamedesigner");
  const artists = byType("boardgameartist");

  const stats = item.statistics?.ratings;
  const ranks = asArray<any>(stats?.ranks?.rank);
  const boardgameRank = ranks.find((r) => r.name === "boardgame");
  const rankValue = boardgameRank?.value;

  return {
    bggId,
    name: primaryName?.value ?? "Unknown",
    yearPublished: item.yearpublished?.value ? Number(item.yearpublished.value) : undefined,
    image: item.image ?? undefined,
    thumbnail: item.thumbnail ?? undefined,
    description: item.description ? decodeHtmlEntities(String(item.description)) : undefined,
    minPlayers: item.minplayers?.value ? Number(item.minplayers.value) : undefined,
    maxPlayers: item.maxplayers?.value ? Number(item.maxplayers.value) : undefined,
    playingTime: item.playingtime?.value ? Number(item.playingtime.value) : undefined,
    minPlayTime: item.minplaytime?.value ? Number(item.minplaytime.value) : undefined,
    maxPlayTime: item.maxplaytime?.value ? Number(item.maxplaytime.value) : undefined,
    minAge: item.minage?.value ? Number(item.minage.value) : undefined,
    weight: stats?.averageweight?.value ? Number(stats.averageweight.value) : undefined,
    bggRating: stats?.average?.value ? Number(stats.average.value) : undefined,
    bggRank: rankValue && rankValue !== "Not Ranked" ? Number(rankValue) : undefined,
    categories,
    mechanics,
    designers,
    artists,
  };
}

export function bggArtistUrl(artistBggId: number): string {
  return `https://boardgamegeek.com/boardgameartist/${artistBggId}`;
}

export function bggDesignerUrl(designerBggId: number): string {
  return `https://boardgamegeek.com/boardgamedesigner/${designerBggId}`;
}

export function howToPlayYoutubeUrl(gameName: string): string {
  const query = encodeURIComponent(`${gameName} how to play`);
  return `https://www.youtube.com/results?search_query=${query}`;
}
