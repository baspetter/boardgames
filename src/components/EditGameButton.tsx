"use client";

import { useState } from "react";
import EditGameModal, { EditableGame } from "@/components/EditGameModal";

export default function EditGameButton({ game }: { game: EditableGame }) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <button
        onClick={() => setOpen(true)}
        className="rounded bg-surfaceHover px-4 py-2 text-center font-semibold hover:bg-white/20"
      >
        Bewerken
      </button>
      {open && <EditGameModal game={game} onClose={() => setOpen(false)} />}
    </>
  );
}
