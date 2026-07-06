#!/bin/sh
set -e

echo "Running database migrations..."
node_modules/.bin/prisma migrate deploy

echo "Seeding invite code (no-op if one already exists)..."
node prisma/seed.cjs

echo "Starting app..."
exec "$@"
