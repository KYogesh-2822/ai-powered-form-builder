import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, Inbox, Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

type Submission = {
    reference: string;
    answers: Record<string, unknown>;
    submitted_at: string;
};

type Props = {
    form: { uuid: string; title: string };
    fields: { key: string; label: string; type: string }[];
    submissions: Paginated<Submission>;
    filters: { search: string };
};

function display(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (typeof value === 'object') {
        const file = value as { name?: string };

        return file.name ?? JSON.stringify(value);
    }

    return String(value);
}

export default function SubmissionsIndex({
    form,
    fields,
    submissions,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);

    function submitSearch(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            `/forms/${form.uuid}/submissions`,
            search ? { search } : {},
            { preserveState: true },
        );
    }

    return (
        <>
            <Head title={`${form.title} responses`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="mb-2 -ml-3"
                        >
                            <Link href={`/forms/${form.uuid}/edit`}>
                                <ArrowLeft /> Back to builder
                            </Link>
                        </Button>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {form.title}
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            {submissions.total} recorded responses
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <a href={`/forms/${form.uuid}/submissions/export`}>
                            <Download /> Export CSV
                        </a>
                    </Button>
                </div>

                <form onSubmit={submitSearch} className="flex max-w-lg gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search any answer..."
                            className="pl-9"
                        />
                    </div>
                    <Button type="submit" variant="outline">
                        Search
                    </Button>
                </form>

                {submissions.data.length === 0 ? (
                    <Card className="border-dashed py-16 text-center">
                        <CardContent>
                            <Inbox className="mx-auto mb-4 size-10 text-muted-foreground" />
                            <h2 className="font-semibold">No responses yet</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Published form responses will appear here.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="overflow-hidden rounded-xl border bg-card">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Submitted</th>
                                        {fields.slice(0, 5).map((field) => (
                                            <th
                                                key={field.key}
                                                className="min-w-44 px-4 py-3"
                                            >
                                                {field.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {submissions.data.map((submission) => (
                                        <tr
                                            key={submission.reference}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                {new Date(
                                                    submission.submitted_at,
                                                ).toLocaleString()}
                                            </td>
                                            {fields.slice(0, 5).map((field) => (
                                                <td
                                                    key={field.key}
                                                    className="max-w-72 truncate px-4 py-3 text-muted-foreground"
                                                >
                                                    {display(
                                                        submission.answers[
                                                            field.key
                                                        ],
                                                    )}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
