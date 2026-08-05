import { Head, useForm } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    LoaderCircle,
    Sparkles,
    Star,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormField, FormSchema } from '@/types';

type Props = {
    form: {
        title: string;
        slug: string;
        description: string | null;
        schema: FormSchema;
    };
    submitted: boolean;
};

type Answer = string | number | boolean | File | null;

export default function PublicForm({ form, submitted }: Props) {
    const [step, setStep] = useState(0);
    const defaults = useMemo(
        () =>
            Object.fromEntries(
                form.schema.sections
                    .flatMap((section) => section.fields)
                    .filter((field) => field.key)
                    .map((field) => [field.key, field.defaultValue ?? '']),
            ),
        [form.schema],
    );
    const { data, setData, post, processing, errors } = useForm<{
        answers: Record<string, Answer>;
        website: string;
        started_at: string;
    }>({
        answers: defaults,
        website: '',
        started_at: new Date().toISOString(),
    });
    const section = form.schema.sections[step];
    const lastStep = step === form.schema.sections.length - 1;
    const formError = (errors as Record<string, string>).form;

    function submit(event: React.FormEvent) {
        event.preventDefault();

        if (!lastStep) {
            setStep((current) => current + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });

            return;
        }

        post(`/f/${form.slug}`, { forceFormData: true, preserveScroll: true });
    }

    if (submitted) {
        return (
            <PublicShell title={form.title}>
                <div className="py-16 text-center">
                    <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <Check className="size-7" />
                    </div>
                    <h1 className="mt-5 text-2xl font-bold">
                        Response received
                    </h1>
                    <p className="mx-auto mt-2 max-w-md text-slate-600">
                        {form.schema.settings.successMessage}
                    </p>
                </div>
            </PublicShell>
        );
    }

    return (
        <>
            <Head title={form.title} />
            <PublicShell title={form.title}>
                <div className="mb-8">
                    <div className="flex items-center justify-between text-xs font-semibold tracking-wide text-violet-700 uppercase">
                        <span>
                            Section {step + 1} of {form.schema.sections.length}
                        </span>
                        <span>
                            {Math.round(
                                ((step + 1) / form.schema.sections.length) *
                                    100,
                            )}
                            %
                        </span>
                    </div>
                    {form.schema.settings.showProgress && (
                        <div className="mt-2 h-2 overflow-hidden rounded-full bg-violet-100">
                            <div
                                className="h-full rounded-full bg-violet-600 transition-all"
                                style={{
                                    width: `${((step + 1) / form.schema.sections.length) * 100}%`,
                                }}
                            />
                        </div>
                    )}
                </div>

                <form onSubmit={submit}>
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold tracking-tight">
                            {section.title}
                        </h2>
                        {section.description && (
                            <p className="mt-2 text-slate-600">
                                {section.description}
                            </p>
                        )}
                    </div>

                    <div className="space-y-7">
                        {section.fields.map((field) => (
                            <PublicField
                                key={field.id}
                                field={field}
                                value={data.answers[field.key]}
                                answers={data.answers}
                                error={
                                    field.key
                                        ? errors[`answers.${field.key}`]
                                        : undefined
                                }
                                onChange={(value) =>
                                    setData('answers', {
                                        ...data.answers,
                                        [field.key]: value,
                                    })
                                }
                            />
                        ))}
                    </div>

                    <input
                        tabIndex={-1}
                        autoComplete="off"
                        value={data.website}
                        onChange={(e) => setData('website', e.target.value)}
                        className="hidden"
                        aria-hidden="true"
                    />
                    {formError && (
                        <p className="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                            {formError}
                        </p>
                    )}
                    <div className="mt-10 flex justify-between border-t pt-6">
                        <button
                            type="button"
                            disabled={step === 0}
                            onClick={() => setStep((current) => current - 1)}
                            className="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 disabled:invisible"
                        >
                            <ChevronLeft /> Back
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex min-w-32 items-center justify-center gap-2 rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700 disabled:opacity-60"
                        >
                            {processing ? (
                                <LoaderCircle className="animate-spin" />
                            ) : lastStep ? (
                                form.schema.settings.submitLabel
                            ) : (
                                <>
                                    Continue <ChevronRight />
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </PublicShell>
        </>
    );
}

function PublicField({
    field,
    value,
    answers,
    error,
    onChange,
}: {
    field: FormField;
    value: Answer;
    answers: Record<string, Answer>;
    error?: string;
    onChange: (value: Answer) => void;
}) {
    if (field.condition) {
        const source = answers[field.condition.field];
        const visible =
            field.condition.operator === 'equals'
                ? String(source ?? '') === field.condition.value
                : field.condition.operator === 'not_equals'
                  ? String(source ?? '') !== field.condition.value
                  : String(source ?? '').includes(field.condition.value);

        if (!visible) {
            return null;
        }
    }

    if (field.type === 'heading') {
        return (
            <div className="border-b pb-2">
                <h3 className="text-lg font-bold">{field.label}</h3>
                {field.helpText && (
                    <p className="mt-1 text-sm text-slate-500">
                        {field.helpText}
                    </p>
                )}
            </div>
        );
    }

    const inputClass = `mt-2 w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-violet-500 focus:ring-3 focus:ring-violet-100 ${error ? 'border-red-500' : 'border-slate-300'}`;

    return (
        <div>
            <label className="text-sm font-semibold text-slate-800">
                {field.label}
                {field.required && <span className="ml-1 text-red-500">*</span>}
            </label>
            {field.helpText && (
                <p className="mt-1 text-sm text-slate-500">{field.helpText}</p>
            )}
            {field.type === 'textarea' ? (
                <textarea
                    value={String(value ?? '')}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={field.placeholder}
                    className={`${inputClass} min-h-28`}
                />
            ) : field.type === 'dropdown' ? (
                <select
                    value={String(value ?? '')}
                    onChange={(e) => onChange(e.target.value)}
                    className={inputClass}
                >
                    <option value="">Select an option</option>
                    {field.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ) : field.type === 'radio' ? (
                <div className="mt-3 space-y-2">
                    {field.options.map((option) => (
                        <label
                            key={option.value}
                            className="flex cursor-pointer items-center gap-3 rounded-lg border p-3 hover:bg-violet-50"
                        >
                            <input
                                type="radio"
                                name={field.key}
                                value={option.value}
                                checked={value === option.value}
                                onChange={() => onChange(option.value)}
                                className="accent-violet-600"
                            />
                            {option.label}
                        </label>
                    ))}
                </div>
            ) : field.type === 'checkbox' ? (
                <label className="mt-3 flex cursor-pointer items-center gap-3 rounded-lg border p-3">
                    <input
                        type="checkbox"
                        checked={Boolean(value)}
                        onChange={(e) => onChange(e.target.checked)}
                        className="size-4 accent-violet-600"
                    />
                    <span>{field.placeholder || 'Yes'}</span>
                </label>
            ) : field.type === 'rating' ? (
                <div className="mt-3 flex gap-2">
                    {[1, 2, 3, 4, 5].map((rating) => (
                        <button
                            key={rating}
                            type="button"
                            onClick={() => onChange(rating)}
                            aria-label={`${rating} stars`}
                        >
                            <Star
                                className={`size-8 ${Number(value) >= rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`}
                            />
                        </button>
                    ))}
                </div>
            ) : field.type === 'file' ? (
                <input
                    type="file"
                    onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                    accept={field.validation.fileTypes
                        .map((type) => `.${type}`)
                        .join(',')}
                    className={inputClass}
                />
            ) : (
                <input
                    type={field.type === 'phone' ? 'tel' : field.type}
                    value={
                        typeof value === 'string' || typeof value === 'number'
                            ? value
                            : ''
                    }
                    onChange={(e) =>
                        onChange(
                            field.type === 'number'
                                ? e.target.value === ''
                                    ? ''
                                    : Number(e.target.value)
                                : e.target.value,
                        )
                    }
                    placeholder={field.placeholder}
                    min={field.validation.min ?? undefined}
                    max={field.validation.max ?? undefined}
                    className={inputClass}
                />
            )}
            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    );
}

function PublicShell({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_#ede9fe,_transparent_42%),linear-gradient(to_bottom,_#f8fafc,_#ffffff)] px-4 py-8 text-slate-950 sm:py-14">
            <div className="mx-auto max-w-2xl">
                <div className="mb-5 flex items-center justify-between">
                    <div className="flex items-center gap-2 text-sm font-bold text-violet-700">
                        <span className="flex size-8 items-center justify-center rounded-lg bg-violet-600 text-white">
                            <Sparkles className="size-4" />
                        </span>
                        FormForge
                    </div>
                    <span className="text-xs text-slate-500">Secure form</span>
                </div>
                <section className="rounded-2xl border border-white/70 bg-white/95 p-6 shadow-xl shadow-violet-100/60 sm:p-10">
                    <h1 className="text-3xl font-bold tracking-tight">
                        {title}
                    </h1>
                    {children}
                </section>
                <p className="mt-5 text-center text-xs text-slate-400">
                    Never submit passwords or sensitive credentials through
                    public forms.
                </p>
            </div>
        </main>
    );
}
