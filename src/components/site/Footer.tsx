import { CLUB } from "@/lib/config";
import { InstagramIcon } from "../ui/icons";
import { Logo } from "./Header";

export function Footer() {
  return (
    <footer className="border-t border-white/[0.06] py-10">
      <div className="container-x flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
        <div>
          <Logo />
          <p className="mt-3 text-sm text-neutral-400">
            © {new Date().getFullYear()} {CLUB.fullName}. {CLUB.address}
          </p>
        </div>
        <div className="flex items-center gap-4 text-sm text-mute">
          <span>{CLUB.hoursLabel}</span>
          <a href={CLUB.instagram} target="_blank" rel="noopener noreferrer" aria-label="Instagram" className="rounded-lg p-2 hover:text-white">
            <InstagramIcon />
          </a>
        </div>
      </div>
    </footer>
  );
}
