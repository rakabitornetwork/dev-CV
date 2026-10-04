import { useEffect, useState } from 'react';
import { CvIcon, formatMonth, sectionIcons } from './icons';

const collections = {
    experience: 'experiences',
    education: 'educations',
    skills: 'skills',
    projects: 'projects',
    testimonials: 'testimonials',
};

export default function App({ cv }) {
    const profile = cv?.profile;
    const sections = cv?.sections ?? [];
    const [theme, setTheme] = useState(() => (localStorage.getItem('cv-theme') === 'light' ? 'light' : 'dark'));
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem('cv-theme', theme);
    }, [theme]);

    if (!profile) {
        return (
            <main className="grid min-h-screen place-items-center px-6">
                <div className="max-w-md text-center">
                    <p className="font-display text-sm tracking-[0.22em] text-cv-accent uppercase">Curriculum vitae</p>
                    <h1 className="mt-4 font-display text-4xl">CV ini belum diisi.</h1>
                    <p className="mt-3 text-cv-muted">Masuk ke panel admin untuk menambahkan profil dan bagian halaman.</p>
                </div>
            </main>
        );
    }

    return (
        <div className="min-h-screen overflow-x-clip">
            <header className="sticky top-0 z-30 border-b border-cv-line/80 bg-cv-bg/80 backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
                    <a href="#atas" className="font-display text-lg tracking-tight">
                        {profile.name}
                    </a>
                    <nav className="hidden items-center gap-5 text-sm text-cv-muted lg:flex">
                        {sections.map((section) => (
                            <a key={section.key} href={`#${section.key}`} className="transition hover:text-cv-ink">
                                {section.title}
                            </a>
                        ))}
                    </nav>
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            className="grid size-10 place-items-center rounded-full border border-cv-line"
                            onClick={() => setTheme((current) => (current === 'dark' ? 'light' : 'dark'))}
                            aria-label={theme === 'dark' ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'}
                        >
                            <CvIcon name={theme === 'dark' ? 'Sun' : 'Moon'} className="size-4" />
                        </button>
                        <button
                            type="button"
                            className="grid size-10 place-items-center rounded-full border border-cv-line lg:hidden"
                            onClick={() => setMenuOpen((open) => !open)}
                            aria-expanded={menuOpen}
                            aria-label="Buka menu"
                        >
                            <CvIcon name={menuOpen ? 'X' : 'Menu'} className="size-4" />
                        </button>
                    </div>
                </div>
                {menuOpen && (
                    <nav className="border-t border-cv-line px-5 py-4 lg:hidden">
                        <div className="flex flex-col gap-3">
                            {sections.map((section) => (
                                <a
                                    key={section.key}
                                    href={`#${section.key}`}
                                    className="text-base"
                                    onClick={() => setMenuOpen(false)}
                                >
                                    {section.title}
                                </a>
                            ))}
                        </div>
                    </nav>
                )}
            </header>

            <main>
                <Hero profile={profile} socials={cv.social_links ?? []} />
                {sections.map((section, index) => (
                    <SectionBlock
                        key={section.key}
                        section={section}
                        index={index + 1}
                        cv={cv}
                        profile={profile}
                    />
                ))}
            </main>

            <footer className="border-t border-cv-line">
                <div className="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-8 text-sm text-cv-muted sm:flex-row sm:items-center sm:justify-between">
                    <p>{profile.name}</p>
                    <p>{[profile.location, new Date().getFullYear()].filter(Boolean).join(' · ')}</p>
                </div>
            </footer>
        </div>
    );
}

function Hero({ profile, socials }) {
    return (
        <section id="atas" className="relative">
            <div className="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(ellipse_at_top,var(--cv-accent-soft),transparent_68%)]" />
            <div className="relative mx-auto grid max-w-6xl items-end gap-12 px-5 pt-16 pb-20 lg:grid-cols-12 lg:pt-24">
                <div className="lg:col-span-7">
                    {profile.availability_label && (
                        <p className="inline-flex items-center gap-2 rounded-full border border-cv-line bg-cv-elevated px-3 py-1 text-sm text-cv-muted">
                            <span className="size-2 rounded-full bg-cv-accent" />
                            {profile.availability_label}
                        </p>
                    )}
                    <h1 className="mt-6 font-display text-[3.15rem] leading-[0.92] tracking-tight text-balance sm:text-7xl lg:text-8xl">
                        {profile.name}
                    </h1>
                    <p className="mt-6 max-w-xl text-xl leading-snug text-cv-muted text-pretty">{profile.headline}</p>
                    <p className="mt-4 max-w-xl text-base leading-relaxed text-pretty">{profile.summary}</p>
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        {profile.cv_url && (
                            <a
                                href={profile.cv_url}
                                className="inline-flex items-center justify-center gap-2 rounded-full bg-cv-ink px-5 py-3 text-sm text-cv-bg"
                            >
                                <CvIcon name="Download" className="size-4" />
                                Unduh CV
                            </a>
                        )}
                        <a
                            href={profile.email ? `mailto:${profile.email}` : '#contact'}
                            className="inline-flex items-center justify-center gap-2 rounded-full border border-cv-line px-5 py-3 text-sm"
                        >
                            <CvIcon name="Mail" className="size-4" />
                            Hubungi saya
                        </a>
                    </div>
                    {socials.length > 0 && (
                        <div className="mt-8 flex flex-wrap gap-2">
                            {socials.map((social) => (
                                <a
                                    key={social.id}
                                    href={social.url}
                                    className="inline-flex items-center gap-2 rounded-full border border-cv-line px-3 py-2 text-sm text-cv-muted hover:text-cv-ink"
                                    target={social.url.startsWith('http') ? '_blank' : undefined}
                                    rel={social.url.startsWith('http') ? 'noreferrer' : undefined}
                                >
                                    <CvIcon name={social.icon} className="size-4" />
                                    {social.label}
                                </a>
                            ))}
                        </div>
                    )}
                </div>
                <div className="lg:col-span-5">
                    <div className="relative mx-auto max-w-sm">
                        <div className="absolute top-6 -left-3 hidden h-full w-full rounded-[2rem] border border-cv-accent/50 lg:block" />
                        <div className="relative overflow-hidden rounded-[2rem] border border-cv-line bg-cv-elevated">
                            {profile.photo_url ? (
                                <img src={profile.photo_url} alt="" className="aspect-[4/5] w-full object-cover" />
                            ) : (
                                <div className="grid aspect-[4/5] place-items-center font-display text-6xl">
                                    {initials(profile.name)}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function SectionBlock({ section, index, cv, profile }) {
    const items = cv[collections[section.key]] ?? [];

    return (
        <section id={section.key} className="scroll-mt-24 border-t border-cv-line py-16 sm:py-20">
            <div className="mx-auto grid max-w-6xl gap-8 px-5 md:grid-cols-12">
                <div className="md:col-span-4">
                    <p className="font-display text-sm tracking-[0.22em] text-cv-accent">
                        {String(index).padStart(2, '0')}
                    </p>
                    <div className="mt-3 flex items-center gap-3">
                        <CvIcon name={sectionIcons[section.key] || 'Sparkles'} className="size-5 text-cv-accent" />
                        <h2 className="font-display text-4xl tracking-tight">{section.title}</h2>
                    </div>
                </div>
                <div className="min-w-0 md:col-span-8">
                    {section.key === 'about' && <About bio={profile.bio} />}
                    {section.key === 'experience' && <Experience items={items} />}
                    {section.key === 'education' && <Education items={items} />}
                    {section.key === 'skills' && <Skills items={items} />}
                    {section.key === 'projects' && <Projects items={items} />}
                    {section.key === 'testimonials' && <Testimonials items={items} />}
                    {section.key === 'contact' && <Contact profile={profile} socials={cv.social_links ?? []} />}
                </div>
            </div>
        </section>
    );
}

function Empty({ children }) {
    return <p className="rounded-2xl border border-dashed border-cv-line px-5 py-8 text-cv-muted">{children}</p>;
}

function About({ bio }) {
    if (!bio) {
        return <Empty>Biografi belum diisi.</Empty>;
    }

    return (
        <div className="space-y-4 text-lg leading-relaxed text-pretty">
            {bio.split('\n').filter(Boolean).map((paragraph) => (
                <p key={paragraph.slice(0, 24)}>{paragraph}</p>
            ))}
        </div>
    );
}

function Experience({ items }) {
    if (items.length === 0) {
        return <Empty>Belum ada pengalaman.</Empty>;
    }

    return (
        <ol className="relative space-y-8 border-l border-cv-line pl-6">
            {items.map((item) => (
                <li key={item.id} className="relative">
                    <span className="absolute top-1.5 -left-[1.72rem] size-3 rounded-full border-2 border-cv-accent bg-cv-bg" />
                    <p className="text-sm text-cv-accent">
                        {formatMonth(item.start_date)} — {item.is_current ? 'Sekarang' : formatMonth(item.end_date)}
                    </p>
                    <h3 className="mt-1 font-display text-2xl">{item.role}</h3>
                    <p className="text-cv-muted">
                        {item.company}
                        {item.location ? ` · ${item.location}` : ''}
                    </p>
                    <p className="mt-3 leading-relaxed text-pretty">{item.description}</p>
                </li>
            ))}
        </ol>
    );
}

function Education({ items }) {
    if (items.length === 0) {
        return <Empty>Belum ada pendidikan.</Empty>;
    }

    return (
        <div className="space-y-6">
            {items.map((item) => (
                <article key={item.id} className="rounded-3xl border border-cv-line bg-cv-elevated p-5">
                    <p className="text-sm text-cv-accent">
                        {item.start_year}
                        {item.end_year ? ` — ${item.end_year}` : ''}
                    </p>
                    <h3 className="mt-1 font-display text-2xl">{item.degree}</h3>
                    <p className="text-cv-muted">
                        {item.school}
                        {item.field ? ` · ${item.field}` : ''}
                    </p>
                    {item.description && <p className="mt-3 leading-relaxed">{item.description}</p>}
                </article>
            ))}
        </div>
    );
}

function Skills({ items }) {
    if (items.length === 0) {
        return <Empty>Belum ada keahlian.</Empty>;
    }

    return (
        <div className="grid gap-5 sm:grid-cols-2">
            {items.map((item) => (
                <div key={item.id}>
                    <div className="mb-2 flex items-baseline justify-between gap-3">
                        <p className="font-medium">{item.name}</p>
                        <p className="text-sm text-cv-muted">{item.level}</p>
                    </div>
                    <div className="h-1.5 overflow-hidden rounded-full bg-cv-line" role="presentation">
                        <div className="h-full rounded-full bg-cv-accent" style={{ width: `${item.level}%` }} />
                    </div>
                    {item.category && <p className="mt-2 text-xs tracking-wide text-cv-muted uppercase">{item.category}</p>}
                </div>
            ))}
        </div>
    );
}

function Projects({ items }) {
    if (items.length === 0) {
        return <Empty>Belum ada proyek.</Empty>;
    }

    return (
        <div className="grid gap-5">
            {items.map((item) => {
                const card = (
                    <>
                        <div className="overflow-hidden bg-cv-accent-soft">
                            {item.cover_url ? (
                                <img src={item.cover_url} alt="" className="aspect-[16/10] w-full object-cover" />
                            ) : (
                                <div className="grid aspect-[16/10] place-items-center font-display text-3xl">{item.title}</div>
                            )}
                        </div>
                        <div className="p-5">
                            <div className="flex items-start justify-between gap-3">
                                <h3 className="font-display text-2xl">{item.title}</h3>
                                {item.url && <CvIcon name="ArrowUpRight" className="mt-1 size-5 shrink-0" />}
                            </div>
                            <p className="mt-2 leading-relaxed text-cv-muted text-pretty">{item.summary}</p>
                            {item.tech_stack?.length > 0 && (
                                <ul className="mt-4 flex flex-wrap gap-2">
                                    {item.tech_stack.map((tech) => (
                                        <li key={tech} className="rounded-full border border-cv-line px-2.5 py-1 text-xs">
                                            {tech}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </>
                );

                const className = 'block overflow-hidden rounded-[1.6rem] border border-cv-line bg-cv-elevated';

                return item.url ? (
                    <a key={item.id} href={item.url} target="_blank" rel="noreferrer" className={className}>
                        {card}
                    </a>
                ) : (
                    <article key={item.id} className={className}>
                        {card}
                    </article>
                );
            })}
        </div>
    );
}

function Testimonials({ items }) {
    if (items.length === 0) {
        return <Empty>Belum ada testimoni.</Empty>;
    }

    return (
        <div className="space-y-5">
            {items.map((item) => (
                <figure key={item.id} className="rounded-[1.6rem] border border-cv-line bg-cv-elevated p-5 sm:p-6">
                    <CvIcon name="Quote" className="size-5 text-cv-accent" />
                    <blockquote className="mt-4 font-display text-2xl leading-snug text-pretty">{item.quote}</blockquote>
                    <figcaption className="mt-5 flex items-center gap-3">
                        {item.avatar_url ? (
                            <img src={item.avatar_url} alt="" className="size-11 rounded-full object-cover" />
                        ) : (
                            <span className="grid size-11 place-items-center rounded-full bg-cv-accent-soft text-sm">
                                {initials(item.name)}
                            </span>
                        )}
                        <span>
                            <span className="block font-medium">{item.name}</span>
                            <span className="block text-sm text-cv-muted">
                                {item.role}
                                {item.company ? ` · ${item.company}` : ''}
                            </span>
                        </span>
                    </figcaption>
                </figure>
            ))}
        </div>
    );
}

function Contact({ profile, socials }) {
    const rows = [
        profile.email && { icon: 'Mail', label: profile.email, href: `mailto:${profile.email}` },
        profile.phone && { icon: 'Phone', label: profile.phone, href: `tel:${profile.phone.replace(/\s/g, '')}` },
        profile.location && { icon: 'MapPin', label: profile.location },
    ].filter(Boolean);

    if (rows.length === 0 && socials.length === 0) {
        return <Empty>Kontak belum diisi.</Empty>;
    }

    return (
        <div className="space-y-4">
            {rows.map((row) => {
                const body = (
                    <span className="flex items-center gap-3 rounded-2xl border border-cv-line bg-cv-elevated px-4 py-4">
                        <CvIcon name={row.icon} className="size-5 text-cv-accent" />
                        <span className="min-w-0 break-words">{row.label}</span>
                    </span>
                );

                return row.href ? (
                    <a key={row.label} href={row.href} className="block">
                        {body}
                    </a>
                ) : (
                    <div key={row.label}>{body}</div>
                );
            })}
            {socials.length > 0 && (
                <div className="flex flex-wrap gap-2 pt-2">
                    {socials.map((social) => (
                        <a
                            key={social.id}
                            href={social.url}
                            className="inline-flex items-center gap-2 rounded-full border border-cv-line px-3 py-2 text-sm"
                            target={social.url.startsWith('http') ? '_blank' : undefined}
                            rel={social.url.startsWith('http') ? 'noreferrer' : undefined}
                        >
                            <CvIcon name={social.icon} className="size-4" />
                            {social.label}
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
}

function initials(name = '') {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0] || '')
        .join('')
        .toUpperCase();
}
