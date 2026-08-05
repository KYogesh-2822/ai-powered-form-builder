import { Head, Link, router } from '@inertiajs/react';
import { Eye, FileText, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { FormSummary, Paginated } from '@/types';

type Props = {
    forms: Paginated<FormSummary>;
    filters: { search: string };
};

export default function FormsIndex({ forms, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    function applySearch(event: React.FormEvent) {
        event.preventDefault();
        router.get('/forms', search ? { search } : {}, { preserveState: true });
    }

    function createForm() {
        router.post('/forms', { title: 'Untitled form' });
    }

    function removeForm(form: FormSummary) {
        if (confirm(`Delete "${form.title}" and all its submissions?`)) {
            router.delete(`/forms/${form.uuid}`);
        }
    }

    return (
        <>
            <Head title="Forms" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold tracking-wide text-violet-600 uppercase">
                            Workspace
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Forms
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            Build, publish, and monitor every form from one
                            place.
                        </p>
                    </div>
                    <Button onClick={createForm} size="lg">
                        <Plus /> New form
                    </Button>
                </div>

                <form onSubmit={applySearch} className="flex max-w-lg gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search forms..."
                            className="pl-9"
                        />
                    </div>
                    <Button type="submit" variant="outline">
                        Search
                    </Button>
                </form>

                {forms.data.length === 0 ? (
                    <Card className="border-dashed py-14 text-center">
                        <CardContent>
                            <FileText className="mx-auto mb-4 size-10 text-violet-500" />
                            <h2 className="text-lg font-semibold">
                                No forms found
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Create your first form or adjust the search.
                            </p>
                            <Button onClick={createForm} className="mt-5">
                                <Plus /> Create a form
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {forms.data.map((form) => (
                            <Card
                                key={form.uuid}
                                className="group gap-4 transition-shadow hover:shadow-md"
                            >
                                <CardHeader className="flex-row items-start justify-between gap-4">
                                    <div className="min-w-0">
                                        <Badge
                                            variant={
                                                form.status === 'published'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {form.status}
                                        </Badge>
                                        <CardTitle className="mt-3 truncate text-lg">
                                            {form.title}
                                        </CardTitle>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeForm(form)}
                                        title="Delete form"
                                    >
                                        <Trash2 />
                                    </Button>
                                </CardHeader>
                                <CardContent className="mt-auto">
                                    <div className="mb-5 flex items-center justify-between text-sm text-muted-foreground">
                                        <span>
                                            {form.submissions_count} responses
                                        </span>
                                        <span>
                                            {new Date(
                                                form.updated_at,
                                            ).toLocaleDateString()}
                                        </span>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button asChild className="flex-1">
                                            <Link
                                                href={`/forms/${form.uuid}/edit`}
                                            >
                                                Open builder
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="icon"
                                            title="Submissions"
                                        >
                                            <Link
                                                href={`/forms/${form.uuid}/submissions`}
                                            >
                                                <Eye />
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {forms.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {forms.links.map((link) => (
                            <Button
                                key={link.label}
                                asChild={Boolean(link.url)}
                                disabled={!link.url}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
