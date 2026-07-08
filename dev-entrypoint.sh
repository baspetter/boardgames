#!/bin/sh
set -e

echo "Installing dependencies..."
npm install

echo "Generating Prisma client..."
npx prisma generate

echo "Running database migrations..."
npx prisma migrate deploy

echo "Seeding invite code (no-op if one already exists)..."
node prisma/seed.cjs

echo "Starting dev server (hot reload enabled)..."
exec npx next dev -H 0.0.0.0 -p 3000
