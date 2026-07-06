import Image from "next/image";

export default function HeaderBanner() {
  return (
    <div className="relative aspect-[2/1] w-full overflow-hidden sm:aspect-[3/1] lg:aspect-[4/1]">
      <Image
        src="/header-banner.png"
        alt="For the Love of Boardgames"
        fill
        priority
        sizes="100vw"
        className="object-cover"
      />
    </div>
  );
}
