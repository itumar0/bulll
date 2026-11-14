-- CreateSchema
CREATE SCHEMA IF NOT EXISTS "public";

-- CreateTable
CREATE TABLE "PoolTable" (
    "id" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "type" TEXT NOT NULL,
    "capacity" INTEGER NOT NULL,
    "sortOrder" INTEGER NOT NULL DEFAULT 0,

    CONSTRAINT "PoolTable_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Booking" (
    "id" SERIAL NOT NULL,
    "tableId" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "phone" TEXT NOT NULL,
    "guests" INTEGER NOT NULL,
    "comment" TEXT,
    "startAt" TIMESTAMP(3) NOT NULL,
    "endAt" TIMESTAMP(3) NOT NULL,
    "status" TEXT NOT NULL DEFAULT 'PENDING',
    "source" TEXT NOT NULL DEFAULT 'WEBSITE',
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "Booking_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "Booking_tableId_startAt_endAt_idx" ON "Booking"("tableId", "startAt", "endAt");

-- CreateIndex
CREATE INDEX "Booking_startAt_idx" ON "Booking"("startAt");

-- CreateIndex
CREATE INDEX "Booking_status_idx" ON "Booking"("status");

-- AddForeignKey
ALTER TABLE "Booking" ADD CONSTRAINT "Booking_tableId_fkey" FOREIGN KEY ("tableId") REFERENCES "PoolTable"("id") ON DELETE RESTRICT ON UPDATE CASCADE;


-- Club tables (6 regular + 2 VIP rooms)
INSERT INTO "PoolTable" ("id", "name", "type", "capacity", "sortOrder") VALUES
  ('t1', 'Стол 1', 'REGULAR', 6, 1),
  ('t2', 'Стол 2', 'REGULAR', 6, 2),
  ('t3', 'Стол 3', 'REGULAR', 6, 3),
  ('t4', 'Стол 4', 'REGULAR', 6, 4),
  ('t5', 'Стол 5', 'REGULAR', 6, 5),
  ('t6', 'Стол 6', 'REGULAR', 6, 6),
  ('vip1', 'VIP 1', 'VIP', 10, 7),
  ('vip2', 'VIP 2', 'VIP', 10, 8)
ON CONFLICT ("id") DO NOTHING;
