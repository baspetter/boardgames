import { Prisma } from "@prisma/client";
import { prisma } from "@/lib/prisma";
import { getGameDetails, BggGameDetails } from "@/lib/bgg";

const CACHE_TTL_MS = 30 * 24 * 60 * 60 * 1000; // 30 days

function bggDataToPrisma(details: BggGameDetails) {
  return {
    name: details.name,
    yearPublished: details.yearPublished,
    image: details.image,
    thumbnail: details.thumbnail,
    description: details.description,
    minPlayers: details.minPlayers,
    maxPlayers: details.maxPlayers,
    bestPlayers: details.bestPlayers,
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
  };
}

export async function getOrCacheGame(bggId: number) {
  const existing = await prisma.game.findUnique({ where: { bggId } });
  if (existing && Date.now() - existing.cachedAt.getTime() < CACHE_TTL_MS) {
    return existing;
  }

  const details = await getGameDetails(bggId);

  return prisma.game.upsert({
    where: { bggId },
    create: { bggId: details.bggId, ...bggDataToPrisma(details) },
    update: bggDataToPrisma(details),
  });
}

export async function refreshGameFromBgg(gameId: string) {
  const game = await prisma.game.findUnique({ where: { id: gameId } });
  if (!game?.bggId) {
    throw new Error("Dit spel heeft geen BGG-koppeling om te verversen.");
  }

  const details = await getGameDetails(game.bggId);

  return prisma.game.update({
    where: { id: gameId },
    data: bggDataToPrisma(details),
  });
}

export interface ManualGameInput {
  name: string;
  image?: string;
  description?: string;
  yearPublished?: number;
  minPlayers?: number;
  maxPlayers?: number;
  bestPlayers?: number;
  playingTime?: number;
  weight?: number;
  categories?: string[];
  howToPlayUrl?: string;
}

export async function createManualGame(input: ManualGameInput) {
  return prisma.game.create({
    data: {
      isManual: true,
      name: input.name,
      image: input.image,
      description: input.description,
      yearPublished: input.yearPublished,
      minPlayers: input.minPlayers,
      maxPlayers: input.maxPlayers,
      bestPlayers: input.bestPlayers,
      playingTime: input.playingTime,
      weight: input.weight,
      categories: input.categories ?? [],
      howToPlayUrl: input.howToPlayUrl,
    },
  });
}

export interface EditGameInput {
  name?: string;
  image?: string;
  description?: string;
  yearPublished?: number;
  minPlayers?: number;
  maxPlayers?: number;
  bestPlayers?: number;
  playingTime?: number;
  weight?: number;
  categories?: string[];
  howToPlayUrl?: string;
}

export async function updateGame(gameId: string, input: EditGameInput) {
  return prisma.game.update({
    where: { id: gameId },
    data: input,
  });
}
