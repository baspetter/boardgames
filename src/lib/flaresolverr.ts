// FlareSolverr solves BGG's Cloudflare challenge with a real (headless)
// browser once, and hands us back the resulting session cookies + the
// User-Agent it used. We then make our own lightweight fetch() calls to the
// BGG XML API directly with that cookie/UA pair, instead of routing every
// single API call through the browser (which would also mangle raw XML
// responses, since Chrome wraps non-HTML content in its own XML viewer markup).

const FLARESOLVERR_URL = process.env.FLARESOLVERR_URL || "http://flaresolverr:8191/v1";
const SESSION_TTL_MS = 30 * 60 * 1000; // 30 minutes

interface CfSession {
  cookieHeader: string;
  userAgent: string;
  expiresAt: number;
}

let cachedSession: CfSession | null = null;

async function solveChallenge(url: string): Promise<CfSession> {
  const res = await fetch(FLARESOLVERR_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ cmd: "request.get", url, maxTimeout: 60000 }),
  });

  if (!res.ok) {
    throw new Error(`FlareSolverr request failed (${res.status})`);
  }

  const data = await res.json();
  if (data.status !== "ok") {
    throw new Error(`FlareSolverr could not solve the challenge: ${data.message}`);
  }

  const cookies = data.solution.cookies as { name: string; value: string }[];
  const cookieHeader = cookies.map((c) => `${c.name}=${c.value}`).join("; ");

  return {
    cookieHeader,
    userAgent: data.solution.userAgent as string,
    expiresAt: Date.now() + SESSION_TTL_MS,
  };
}

export async function getCfSession(): Promise<CfSession> {
  if (cachedSession && cachedSession.expiresAt > Date.now()) {
    return cachedSession;
  }
  cachedSession = await solveChallenge("https://boardgamegeek.com/xmlapi2/search?type=boardgame&query=catan");
  return cachedSession;
}

export function invalidateCfSession(): void {
  cachedSession = null;
}
