import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Bot,
    CheckCircle2,
    FileText,
    Layers3,
    LockKeyhole,
    Search,
    ShieldCheck,
    Sparkles,
    WandSparkles,
    Zap,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { dashboard, login, register } from '@/routes';
import type { Paginated, PublicFormSummary } from '@/types';

type Props = {
    forms: Paginated<PublicFormSummary>;
    filters: { search: string };
};

const features = [
    {
        icon: WandSparkles,
        label: 'Create faster',
        title: 'Start from an idea or a document',
        copy: 'Build manually, describe a form to AI, or import an existing Word or Excel file.',
    },
    {
        icon: ShieldCheck,
        label: 'Trust every response',
        title: 'One schema, validated everywhere',
        copy: 'The builder, public renderer and server validation all use the same source of truth.',
    },
    {
        icon: BarChart3,
        label: 'Stay in control',
        title: 'Review, search and export results',
        copy: 'Organize submissions, restore earlier versions and export clean data when needed.',
    },
] as const;

export default function Welcome({ forms, filters }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="AI-powered forms, ready to share">
                <meta
                    name="description"
                    content="Create, publish and complete secure forms with FormForge."
                />
            </Head>
            <div className="min-h-screen overflow-hidden bg-[#f7f8fc] text-slate-950">
                <SiteHeader signedIn={Boolean(auth.user)} />
                <main>
                    <Hero signedIn={Boolean(auth.user)} />
                    <Stats />
                    <PublishedForms forms={forms} filters={filters} />
                    <FeatureGrid />
                    <CallToAction signedIn={Boolean(auth.user)} />
                </main>
                <footer className="border-t border-slate-200 bg-white px-5 py-8 sm:px-8">
                    <div className="mx-auto flex max-w-7xl flex-col gap-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <Brand />
                        <span className="inline-flex items-center gap-2">
                            <LockKeyhole className="size-4" /> Owner-only
                            management
                        </span>
                        <span>� {new Date().getFullYear()} FormForge</span>
                    </div>
                </footer>
            </div>
        </>
    );
}

function SiteHeader({ signedIn }: { signedIn: boolean }) {
    return (
        <header className="sticky top-0 z-50 border-b border-slate-200/70 bg-white/85 backdrop-blur-xl">
            <nav className="mx-auto flex h-18 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                <Brand />
                <div className="flex items-center gap-2 sm:gap-3">
                    <a
                        href="#published-forms"
                        className="hidden px-3 py-2 text-sm font-semibold text-slate-600 hover:text-violet-700 sm:block"
                    >
                        Explore forms
                    </a>
                    {signedIn ? (
                        <Link
                            href={dashboard()}
                            className="inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700"
                        >
                            Dashboard <ArrowRight className="size-4" />
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className="px-3 py-2 text-sm font-bold text-slate-700 hover:text-violet-700"
                            >
                                Sign in
                            </Link>
                            <Link
                                href={register()}
                                className="hidden items-center gap-2 rounded-full bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700 sm:inline-flex"
                            >
                                Build a form <ArrowRight className="size-4" />
                            </Link>
                        </>
                    )}
                </div>
            </nav>
        </header>
    );
}

function Brand() {
    return (
        <Link href="/" className="inline-flex items-center gap-2.5">
            <span className="flex size-9 items-center justify-center rounded-xl bg-violet-700 text-white shadow-md shadow-violet-200">
                <Sparkles className="size-4" />
            </span>
            <span className="text-lg font-black tracking-[-0.03em] text-slate-950">
                Form<span className="text-violet-700">Forge</span>
            </span>
        </Link>
    );
}

function Hero({ signedIn }: { signedIn: boolean }) {
    return (
        <section className="relative isolate border-b border-slate-200 bg-white">
            <div className="absolute inset-0 -z-20 bg-[linear-gradient(to_right,#eef2ff_1px,transparent_1px),linear-gradient(to_bottom,#eef2ff_1px,transparent_1px)] bg-[size:44px_44px] opacity-55" />
            <div className="absolute -top-24 left-1/2 -z-10 size-96 -translate-x-1/2 rounded-full bg-violet-200/70 blur-3xl" />
            <div className="mx-auto grid max-w-7xl items-center gap-14 px-5 py-20 sm:px-8 sm:py-24 lg:grid-cols-[1.04fr_0.96fr] lg:px-10 lg:py-30">
                <div className="max-w-2xl">
                    <div className="mb-7 inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-3.5 py-2 text-xs font-black tracking-[0.15em] text-violet-700 uppercase shadow-sm">
                        <Sparkles className="size-3.5" /> Intelligent forms,
                        human control
                    </div>
                    <h1 className="text-5xl leading-[0.98] font-black tracking-[-0.055em] text-balance sm:text-6xl lg:text-7xl">
                        Forms that turn{' '}
                        <span className="text-violet-700">questions</span> into
                        progress.
                    </h1>
                    <p className="mt-8 max-w-xl text-lg leading-8 text-slate-600 sm:text-xl">
                        Build with drag and drop, generate with AI, import your
                        documents, and publish a secure experience people enjoy
                        completing.
                    </p>
                    <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href={signedIn ? dashboard() : register()}
                            className="inline-flex items-center justify-center gap-2 rounded-full bg-violet-700 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5 hover:bg-violet-800"
                        >
                            {signedIn
                                ? 'Open your workspace'
                                : 'Create your first form'}
                            <ArrowRight className="size-4" />
                        </Link>
                        <a
                            href="#published-forms"
                            className="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-black text-slate-800 shadow-sm hover:border-violet-300 hover:text-violet-700"
                        >
                            Browse public forms
                        </a>
                    </div>
                    <div className="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm font-semibold text-slate-500">
                        {[
                            'No-code builder',
                            'Schema-secure',
                            'Ready to share',
                        ].map((item) => (
                            <span
                                key={item}
                                className="inline-flex items-center gap-2"
                            >
                                <CheckCircle2 className="size-4 text-emerald-600" />
                                {item}
                            </span>
                        ))}
                    </div>
                </div>
                <ProductPreview />
            </div>
        </section>
    );
}

function ProductPreview() {
    return (
        <div className="relative mx-auto w-full max-w-xl lg:mr-0">
            <div className="absolute -top-5 -left-4 z-20 hidden items-center gap-3 rounded-2xl border bg-white/95 px-4 py-3 shadow-xl sm:flex">
                <span className="flex size-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <Zap className="size-4 fill-current" />
                </span>
                <div>
                    <p className="text-xs font-black">Ready in minutes</p>
                    <p className="text-[11px] text-slate-500">
                        AI-assisted creation
                    </p>
                </div>
            </div>
            <div className="rotate-1 rounded-[2rem] border border-violet-300/50 bg-slate-950 p-4 shadow-2xl shadow-violet-300/40 transition hover:rotate-0">
                <div className="flex items-center justify-between px-2 pb-3">
                    <div className="flex gap-2">
                        <span className="size-2.5 rounded-full bg-rose-400" />
                        <span className="size-2.5 rounded-full bg-amber-300" />
                        <span className="size-2.5 rounded-full bg-emerald-400" />
                    </div>
                    <span className="rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold tracking-wider text-slate-300 uppercase">
                        Builder preview
                    </span>
                </div>
                <div className="rounded-[1.4rem] bg-[#f7f5ff] p-4 sm:p-6">
                    <div className="flex items-center justify-between text-xs font-black text-violet-700">
                        <span className="inline-flex items-center gap-2">
                            <Sparkles className="size-4" /> FormForge
                        </span>
                        <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] text-emerald-700 uppercase">
                            � Published
                        </span>
                    </div>
                    <div className="mt-5 rounded-2xl border border-violet-100 bg-white p-5 shadow-lg shadow-violet-100/70 sm:p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-[10px] font-black tracking-wider text-violet-600 uppercase">
                                    Event registration
                                </p>
                                <h3 className="mt-2 text-xl font-black tracking-tight sm:text-2xl">
                                    Tell us about your visit
                                </h3>
                            </div>
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                <Layers3 className="size-5" />
                            </span>
                        </div>
                        <div className="mt-6 space-y-4">
                            <MockField label="Full name" />
                            <MockField label="Work email" />
                            <div className="grid grid-cols-2 gap-2">
                                <div className="rounded-lg border border-violet-200 bg-violet-50 p-3 text-xs font-bold text-violet-700">
                                    %� In person
                                </div>
                                <div className="rounded-lg border border-slate-200 p-3 text-xs text-slate-500">
                                    %� Online
                                </div>
                            </div>
                        </div>
                        <div className="mt-6 flex items-center justify-between border-t pt-4">
                            <span className="text-[10px] font-bold text-slate-400">
                                Step 1 of 2
                            </span>
                            <span className="rounded-lg bg-violet-700 px-4 py-2 text-[11px] font-black text-white">
                                Continue!�
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div className="absolute -right-4 -bottom-6 z-20 hidden items-center gap-3 rounded-2xl border bg-white/95 px-4 py-3 shadow-xl sm:flex">
                <Bot className="size-5 text-violet-700" />
                <div>
                    <p className="text-xs font-black">Schema validated</p>
                    <p className="text-[11px] text-slate-500">
                        Safe before save
                    </p>
                </div>
            </div>
        </div>
    );
}

function MockField({ label }: { label: string }) {
    return (
        <div>
            <p className="mb-2 text-[10px] font-black text-slate-500">
                {label}
            </p>
            <div className="h-10 rounded-lg border border-slate-200 bg-slate-50" />
        </div>
    );
}

function Stats() {
    const stats = [
        ['13', 'purpose-built field types'],
        ['3', 'ways to create a form'],
        ['1', 'validated source of truth'],
        ['24/7', 'public response collection'],
    ];

    return (
        <section className="border-b border-slate-800 bg-slate-950 text-white">
            <div className="mx-auto grid max-w-7xl grid-cols-2 gap-px bg-white/10 lg:grid-cols-4">
                {stats.map(([value, label]) => (
                    <div key={label} className="bg-slate-950 px-6 py-7 sm:px-9">
                        <p className="text-2xl font-black text-violet-300">
                            {value}
                        </p>
                        <p className="mt-1 text-xs font-bold tracking-wide text-slate-400 uppercase">
                            {label}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function PublishedForms({ forms, filters }: Props) {
    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const value = String(
            new FormData(event.currentTarget).get('search') ?? '',
        ).trim();

        router.get('/', value === '' ? {} : { search: value }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    return (
        <section
            id="published-forms"
            className="scroll-mt-22 px-5 py-20 sm:px-8 sm:py-24 lg:px-10"
        >
            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
                    <div className="max-w-2xl">
                        <p className="text-sm font-black tracking-[0.18em] text-violet-700 uppercase">
                            Open for responses
                        </p>
                        <h2 className="mt-3 text-4xl font-black tracking-[-0.04em] sm:text-5xl">
                            Published forms
                        </h2>
                        <p className="mt-4 text-lg leading-8 text-slate-600">
                            Open and submit any form below without an account.
                            Management controls remain private to its owner.
                        </p>
                    </div>
                    <form
                        onSubmit={search}
                        className="flex w-full max-w-md items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm"
                    >
                        <Search className="ml-2 size-5 text-slate-400" />
                        <label htmlFor="form-search" className="sr-only">
                            Search published forms
                        </label>
                        <input
                            id="form-search"
                            name="search"
                            type="search"
                            defaultValue={filters.search}
                            placeholder="Search public forms"
                            className="min-w-0 flex-1 bg-transparent px-1 py-2 text-sm outline-none"
                        />
                        <button
                            type="submit"
                            className="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white hover:bg-violet-700"
                        >
                            Search
                        </button>
                    </form>
                </div>

                {filters.search && (
                    <p className="mt-8 text-sm text-slate-600">
                        {forms.total} {forms.total === 1 ? 'result' : 'results'}{' '}
                        for <strong>{filters.search}</strong>{' '}
                        <Link
                            href="/"
                            preserveScroll
                            className="ml-2 font-black text-violet-700"
                        >
                            Clear
                        </Link>
                    </p>
                )}

                {forms.data.length ? (
                    <div className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {forms.data.map((form) => (
                            <PublicFormCard key={form.slug} form={form} />
                        ))}
                    </div>
                ) : (
                    <EmptyForms searching={Boolean(filters.search)} />
                )}

                <Pagination forms={forms} />
            </div>
        </section>
    );
}

function PublicFormCard({ form }: { form: PublicFormSummary }) {
    return (
        <article className="group relative flex min-h-72 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-violet-200 hover:shadow-xl hover:shadow-violet-100/70">
            <div className="absolute top-0 right-0 size-28 translate-x-10 -translate-y-10 rounded-full bg-violet-50 group-hover:bg-violet-100" />
            <div className="relative flex items-center justify-between">
                <span className="flex size-11 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
                    <FileText className="size-5" />
                </span>
                <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black tracking-wide text-emerald-700 uppercase">
                    � Public
                </span>
            </div>
            <div className="relative mt-6 flex-1">
                <h3 className="text-xl font-black tracking-tight group-hover:text-violet-800">
                    {form.title}
                </h3>
                <p className="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">
                    {form.description ||
                        'This form is open and ready to receive responses.'}
                </p>
            </div>
            <div className="relative mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500">
                <span>
                    {form.fields_count}{' '}
                    {form.fields_count === 1 ? 'field' : 'fields'}
                </span>
                <span>"</span>
                <span>
                    {form.sections_count}{' '}
                    {form.sections_count === 1 ? 'section' : 'sections'}
                </span>
                <span className="ml-auto">
                    {publishedDate(form.published_at)}
                </span>
            </div>
            <Link
                href={form.public_url}
                aria-label={`Open ${form.title}`}
                className="relative mt-5 inline-flex items-center justify-between rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white group-hover:bg-violet-700"
            >
                Open form
                <ArrowRight className="size-4 transition group-hover:translate-x-1" />
            </Link>
        </article>
    );
}

function EmptyForms({ searching }: { searching: boolean }) {
    return (
        <div className="mt-10 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <FileText className="mx-auto size-10 text-violet-500" />
            <h3 className="mt-5 text-xl font-black">
                {searching
                    ? 'No published forms match that search'
                    : 'No forms are public yet'}
            </h3>
            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                {searching
                    ? 'Try a different title or description.'
                    : 'Published forms will automatically appear here and remain read-only from the homepage.'}
            </p>
        </div>
    );
}

function Pagination({ forms }: { forms: Paginated<PublicFormSummary> }) {
    if (forms.last_page === 1) {
        return null;
    }

    return (
        <nav
            aria-label="Published form pages"
            className="mt-10 flex flex-wrap justify-center gap-2"
        >
            {forms.links.map((link, index) => {
                const className = `inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-black ${
                    link.active
                        ? 'bg-violet-700 text-white'
                        : link.url
                          ? 'border bg-white text-slate-700 hover:text-violet-700'
                          : 'cursor-not-allowed border bg-slate-100 text-slate-400'
                }`;
                const label = link.label
                    .replace('&laquo;', '�')
                    .replace('&raquo;', '�');

                return link.url ? (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={className}
                    >
                        {label}
                    </Link>
                ) : (
                    <span
                        key={`${link.label}-${index}`}
                        aria-disabled="true"
                        className={className}
                    >
                        {label}
                    </span>
                );
            })}
        </nav>
    );
}

function FeatureGrid() {
    return (
        <section className="border-y border-slate-200 bg-white px-5 py-20 sm:px-8 sm:py-24 lg:px-10">
            <div className="mx-auto max-w-7xl">
                <p className="text-sm font-black tracking-[0.18em] text-violet-700 uppercase">
                    Built for the whole workflow
                </p>
                <h2 className="mt-3 max-w-3xl text-4xl font-black tracking-[-0.04em] sm:text-5xl">
                    Less form busywork. More useful answers.
                </h2>
                <div className="mt-12 grid gap-5 lg:grid-cols-3">
                    {features.map((feature, index) => {
                        const Icon = feature.icon;

                        return (
                            <article
                                key={feature.title}
                                className="group relative overflow-hidden rounded-3xl border border-slate-200 bg-[#fafaff] p-7 transition hover:-translate-y-1 hover:border-violet-200 hover:shadow-xl hover:shadow-violet-100/70"
                            >
                                <span className="absolute top-5 right-6 text-6xl font-black text-violet-100">
                                    0{index + 1}
                                </span>
                                <div className="relative flex size-12 items-center justify-center rounded-2xl bg-violet-700 text-white shadow-lg shadow-violet-200">
                                    <Icon className="size-5" />
                                </div>
                                <p className="relative mt-8 text-xs font-black tracking-[0.16em] text-violet-700 uppercase">
                                    {feature.label}
                                </p>
                                <h3 className="relative mt-3 text-2xl font-black tracking-tight">
                                    {feature.title}
                                </h3>
                                <p className="relative mt-4 leading-7 text-slate-600">
                                    {feature.copy}
                                </p>
                            </article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function CallToAction({ signedIn }: { signedIn: boolean }) {
    return (
        <section className="px-5 py-20 sm:px-8 lg:px-10">
            <div className="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-violet-700 px-7 py-14 text-white shadow-2xl shadow-violet-200 sm:px-12 lg:flex lg:items-center lg:justify-between lg:px-16">
                <div className="absolute -top-28 -right-20 size-80 rounded-full border-[55px] border-white/10" />
                <div className="relative max-w-2xl">
                    <p className="text-sm font-black tracking-[0.18em] text-violet-200 uppercase">
                        Your next form can be live today
                    </p>
                    <h2 className="mt-3 text-4xl font-black tracking-[-0.04em] sm:text-5xl">
                        Start with a blank canvasor one sentence.
                    </h2>
                </div>
                <Link
                    href={signedIn ? dashboard() : register()}
                    className="relative mt-8 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3.5 text-sm font-black text-violet-800 transition hover:-translate-y-0.5 lg:mt-0"
                >
                    {signedIn ? 'Go to dashboard' : 'Build a form'}
                    <ArrowRight className="size-4" />
                </Link>
            </div>
        </section>
    );
}

function publishedDate(value: string | null): string {
    if (!value) {
        return 'Recently published';
    }

    return new Intl.DateTimeFormat('en', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}
