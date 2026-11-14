// Usage: npm run hash-password -- <password>
// Prints a bcrypt hash ready to paste into .env (the `$` signs are escaped,
// because Next.js expands `$VAR` references inside .env files).
import bcrypt from "bcryptjs";

const password = process.argv[2];
if (!password) {
  console.error("Usage: npm run hash-password -- <password>");
  process.exit(1);
}
const hash = bcrypt.hashSync(password, 12);
console.log("Raw hash:        ", hash);
console.log("For .env (escaped):");
console.log(`ADMIN_PASSWORD_HASH=${hash.replace(/\$/g, "\\$")}`);
