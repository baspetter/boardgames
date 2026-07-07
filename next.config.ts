import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  images: {
    // Self-hosted image optimization needs the native "sharp" package,
    // which isn't installed. Skipping optimization avoids that dependency
    // (and the Alpine/musl binary compatibility issues that can come with
    // it) — images are served as-is instead of resized/re-encoded.
    unoptimized: true,
    remotePatterns: [
      { protocol: "https", hostname: "cf.geekdo-images.com" },
      { protocol: "https", hostname: "*.geekdo-images.com" },
    ],
  },
};

export default nextConfig;
