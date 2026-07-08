import type { Metadata } from "next";
import Navbar from "@/components/Navbar";
import HeaderBanner from "@/components/HeaderBanner";
import "./globals.css";

export const metadata: Metadata = {
  title: "My Game Circle",
  description: "Bordspellencollectie voor jou en je speelgroep",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="nl">
      <body className="min-h-screen bg-background font-sans text-white">
        <HeaderBanner />
        <Navbar />
        <main className="mx-auto max-w-7xl px-6 py-8">{children}</main>
      </body>
    </html>
  );
}
