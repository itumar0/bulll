export function SectionHeading({
  eyebrow,
  title,
  text,
  id,
}: {
  eyebrow: string;
  title: string;
  text?: string;
  id?: string;
}) {
  return (
    <div className="mb-10 max-w-2xl sm:mb-14">
      <p className="mb-3 text-xs font-bold uppercase tracking-[0.25em] text-accent-500">{eyebrow}</p>
      <h2 id={id} className="text-3xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">
        {title}
      </h2>
      {text && <p className="mt-4 text-base leading-relaxed text-mute sm:text-lg">{text}</p>}
    </div>
  );
}
