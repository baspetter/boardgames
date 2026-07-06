import Link from "next/link";
import { auth, signOut } from "@/lib/auth";

export default async function Navbar() {
  const session = await auth();
  if (!session?.user) return null;

  return (
    <header className="sticky top-0 z-30 flex items-center justify-between gap-6 border-b border-white/5 bg-background/95 px-6 py-3 backdrop-blur">
      <div className="flex items-center gap-6">
        <Link href="/" className="text-lg font-bold tracking-tight text-accent whitespace-nowrap">
          For the Love of Boardgames
        </Link>
        <nav className="flex gap-4 text-sm text-white/70">
          <Link href="/" className="hover:text-white">
            Collectie
          </Link>
          <Link href="/playgroups" className="hover:text-white">
            Playgroups
          </Link>
          <Link href="/game-night" className="hover:text-white">
            Speelavond
          </Link>
        </nav>
      </div>
      <div className="flex items-center gap-4 text-sm text-white/70">
        <span>{session.user.name}</span>
        <form
          action={async () => {
            "use server";
            await signOut({ redirectTo: "/login" });
          }}
        >
          <button className="rounded bg-surface px-3 py-1.5 hover:bg-surfaceHover" type="submit">
            Uitloggen
          </button>
        </form>
      </div>
    </header>
  );
}
