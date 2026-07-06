import { Prisma } from "@prisma/client";
import { prisma } from "@/lib/prisma";
import { getGameDetails } from "@/lib/bgg";

const CACHE_TTL_MS = 30 * 24 * 60 * 60 * 1000; // 30 days

export async function getOrCacheGame(bggId: number) {
  const existing = await prisma.game.findUnique({ where: { bggId } });
  if (existing && Date.now() - existing.cachedAt.getTime() < CACHE_TTL_MS) {
    return existing;
  }

  const details = await getGameDetails(bggId);

  return prisma.game.upsert({
    where: { bggId },
    create: {
      bggId: details.bggId,
      name: details.name,
      yearPublished: details.yearPublished,
      image: details.image,
      thumbnail: details.thumbnail,
      description: details.description,
      minPlayers: details.minPlayers,
      maxPlayers: details.maxPlayers,
      playingTime: details.playingTime,
      minPlayTime: details.minPlayTime,
      maxPlayTime: details.maxPlayTime,
      minAge: details.minAge,
      weight: details.weight,
      bggRating: details.bggRating,
      bggRank: details.bggRank,
      categories: details.categories,
      mechanics: details.mechanics,
      designers: details.designers as unknown as Prisma.InputJsonValue,
      artists: details.artists as unknown as Prisma.InputJsonValue,
      cachedAt: new Date(),
    },
    update: {
      name: details.name,
      yearPublished: details.yearPublished,
      image: details.image,
      thumbnail: details.thumbnail,
      description: details.description,
      minPlayers: details.minPlayers,
      maxPlayers: details.maxPlayers,
      playingTime: details.playingTime,
      minPlayTime: details.minPlayTime,
      maxPlayTime: details.maxPlayTime,
      minAge: details.minAge,
      weight: details.weight,
      bggRating: details.bggRating,
      bggRank: details.bggRank,
      categories: details.categories,
      mechanics: details.mechanics,
      designers: details.designers as unknown as Prisma.InputJsonValue,
      artists: details.artists as unknown as Prisma.InputJsonValue,
      cachedAt: new Date(),
    },
  });
}
