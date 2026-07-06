import { randomBytes } from "crypto";

export function generatePlayGroupCode(): string {
  return randomBytes(3).toString("hex").toUpperCase();
}
