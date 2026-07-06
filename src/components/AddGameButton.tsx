"use client";

import { useState } from "react";
import AddGameModal from "@/components/AddGameModal";

export default function AddGameButton() {
  const [open, setOpen] = useState(false);

  return (
    <>
      <button
        onClick={() => setOpen(true)}
        className="rounded bg-accent px-4 py-2 font-semibold hover:bg-accentHover"
      >
        + Spel toevoegen
      </button>
      {open && <AddGameModal onClose={() => setOpen(false)} />}
    </>
  );
}
