import { CLUB } from "@/lib/config";
import { InstagramIcon, MapIcon } from "../ui/icons";
import { Logo } from "./Header";

export function Footer() {
  return (
    <footer className="border-t border-white/[0.06] py-10 pb-28 md:pb-10">
      <div className="container-x flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
        <div>
          <Logo />
          <p className="mt-3 text-sm text-neutral-400">
            © {new Date().getFullYear()} {CLUB.fullName}. {CLUB.address} · {CLUB.hoursLabel}
          </p>
        </div>
        <div className="flex items-center gap-2 text-sm text-mute">
          <a href={CLUB.instagram} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-lg p-2 hover:text-white">
            <InstagramIcon /> Instagram
          </a>
          <a href={CLUB.mapUrl} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-lg p-2 hover:text-white">
            <MapIcon /> 2ГИС
          </a>
        </div>
      </div>
    </footer>
  );
}
