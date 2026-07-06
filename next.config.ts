import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "cf.geekdo-images.com" },
      { protocol: "https", hostname: "*.geekdo-images.com" },
    ],
  },
};

export default nextConfig;
