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

// Serializes every outgoing BGG request (across all users/requests hitting
// this server) and spaces them at least MIN_INTERVAL_MS apart, so concurrent
// searches/adds from multiple people can never burst BGG and trip its abuse
// protection the way a naive retry loop did before.
const MIN_INTERVAL_MS = 2000;
let bggQueue: Promise<unknown> = Promise.resolve();
let lastRequestAt = 0;

function throttled<T>(fn: () => Promise<T>): Promise<T> {
  const result = bggQueue.then(async () => {
    const wait = Math.max(0, lastRequestAt + MIN_INTERVAL_MS - Date.now());
    if (wait > 0) await new Promise((r) => setTimeout(r, wait));
    lastRequestAt = Date.now();
    return fn();
  });
  // Keep the queue alive even if this particular request fails.
  bggQueue = result.catch(() => undefined);
  return result;
}

async function fetchWithRetry(url: string, attempts = 5, delayMs = 1500): Promise<string> {
  return throttled(() => fetchOnce(url, attempts, delayMs));
}

async function fetchOnce(url: string, attempts: number, delayMs: number): Promise<string> {
  for (let i = 0; i < attempts; i++) {
    const res = await fetch(url, {
      headers: {
        Accept: "application/xml,text/xml,*/*",
        "Accept-Language": "en-US,en;q=0.9",
        "User-Agent":
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
      },
    });
    if (res.status === 202) {
      // BGG queued the request for processing (documented behavior); wait and retry.
      await new Promise((r) => setTimeout(r, delayMs));
      continue;
    }
    // 401/403/429 are BGG's own abuse/rate-limit protection, not a transient
    // hiccup — retrying immediately only makes it worse. Fail fast instead.
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

/**
 * Extracts a BGG id from a pasted boardgamegeek.com URL
 * (e.g. https://boardgamegeek.com/boardgame/13/catan) or a bare numeric id.
 * Returns null if nothing recognizable was found.
 */
export function extractBggId(input: string): number | null {
  const trimmed = input.trim();
  if (/^\d+$/.test(trimmed)) {
    return Number(trimmed);
  }
  const match = trimmed.match(/boardgame(?:expansion)?\/(\d+)/i);
  return match ? Number(match[1]) : null;
}
