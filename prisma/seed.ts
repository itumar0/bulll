import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

const TABLES = [
  ...Array.from({ length: 6 }, (_, i) => ({
    id: `t${i + 1}`,
    name: `Стол ${i + 1}`,
    type: "REGULAR",
    capacity: 6,
    sortOrder: i + 1,
  })),
  { id: "vip1", name: "VIP 1", type: "VIP", capacity: 10, sortOrder: 7 },
  { id: "vip2", name: "VIP 2", type: "VIP", capacity: 10, sortOrder: 8 },
];

/** Round a date down to the nearest 30 minutes. */
function floor30(d: Date) {
  const ms = 30 * 60 * 1000;
  return new Date(Math.floor(d.getTime() / ms) * ms);
}
const H = 60 * 60 * 1000;

async function main() {
  for (const t of TABLES) {
    await prisma.poolTable.upsert({ where: { id: t.id }, update: t, create: t });
  }

  await prisma.booking.deleteMany({ where: { phone: { startsWith: "+99670000000" } } });

  const now = floor30(new Date());
  const demo = [
    // Currently playing
    { tableId: "t1", name: "Азамат", phone: "+996700000001", guests: 2, start: -1, hours: 2, status: "CONFIRMED" },
    { tableId: "vip1", name: "Нурлан", phone: "+996700000002", guests: 6, start: -0.5, hours: 3, status: "CONFIRMED" },
    // Starting soon (within the hour)
    { tableId: "t3", name: "Айгерим", phone: "+996700000003", guests: 4, start: 0.5, hours: 2, status: "PENDING" },
    // Later today / tomorrow
    { tableId: "t2", name: "Бакыт", phone: "+996700000004", guests: 3, start: 3, hours: 1, status: "PENDING" },
    { tableId: "vip2", name: "Эрлан", phone: "+996700000005", guests: 8, start: 4, hours: 3, status: "CONFIRMED" },
    { tableId: "t5", name: "Дина", phone: "+996700000006", guests: 2, start: 24, hours: 2, status: "PENDING" },
    // Past
    { tableId: "t4", name: "Тимур", phone: "+996700000007", guests: 2, start: -5, hours: 2, status: "COMPLETED" },
  ];

  let created = 0;
  for (const b of demo) {
    const startAt = new Date(now.getTime() + b.start * H);
    const endAt = new Date(startAt.getTime() + b.hours * H);
    created++;
    await prisma.booking.create({
      data: {
        tableId: b.tableId,
        name: b.name,
        phone: b.phone,
        guests: b.guests,
        status: b.status,
        startAt,
        endAt,
        comment: "Тестовая бронь",
        source: "WEBSITE",
      },
    });
  }
  console.log(`Seeded ${TABLES.length} tables and ${created} demo bookings.`);
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
