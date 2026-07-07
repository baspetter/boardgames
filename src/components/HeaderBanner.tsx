import Image from "next/image";

export default function HeaderBanner() {
  return (
    <div className="relative aspect-[3/1] w-full overflow-hidden sm:aspect-[4/1] lg:aspect-[5/1]">
      <Image
        src="/header-background.png"
        alt=""
        fill
        priority
        sizes="100vw"
        className="object-cover"
      />
      <div className="absolute inset-0 flex items-center justify-center p-2">
        <Image
          src="/header-logo.png"
          alt="For the Love of Boardgames"
          fill
          priority
          sizes="100vw"
          className="object-contain"
        />
      </div>
    </div>
  );
}
