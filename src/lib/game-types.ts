// Fixed list of game types for the manual-entry dropdown. Stored into the
// same `categories` field BGG-sourced games use, so they render consistently
// as tags on the detail page either way.
export const GAME_TYPES = [
  "Strategie",
  "Familie",
  "Party",
  "Coöperatief",
  "Kaartspel",
  "Dobbelspel",
  "Puzzel",
  "Wargame",
  "Kinderspel",
  "Abstract",
  "Overig",
] as const;

export type GameType = (typeof GAME_TYPES)[number];
