import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    FileCheck2,
    FileText,
    Inbox,
    Plus,
    Sparkles,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    stats: {
        forms: number;
        published: number;
        submissions: number;
        submissionsThisMonth: number;
    };
    recentForms: {
        uuid: string;
        title: string;
        status: string;
        submissions_count: number;
        updated_at: string;
    }[];
    recentSubmissions: {
        reference: string;
        submitted_at: string;
        form: { uuid: string; title: string };
    }[];
};

const statCards = [
    {
        key: 'forms',
        label: 'Total forms',
        icon: FileText,
        tone: 'bg-violet-100 text-violet-700 dark:bg-violet-950',
    },
    {
        key: 'published',
        label: 'Published',
        icon: FileCheck2,
        tone: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950',
    },
    {
        key: 'submissions',
        label: 'All responses',
        icon: Inbox,
        tone: 'bg-sky-100 text-sky-700 dark:bg-sky-950',
    },
    {
        key: 'submissionsThisMonth',
        label: 'This month',
        icon: Sparkles,
        tone: 'bg-amber-100 text-amber-700 dark:bg-amber-950',
    },
] as const;

export default function Dashboard({
    stats,
    recentForms,
    recentSubmissions,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold tracking-wide text-violet-600 uppercase">
                            Overview
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Build better forms, faster.
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            Create manually, generate with AI, or import a
                            document.
                        </p>
                    </div>
                    <Button
                        size="lg"
                        onClick={() =>
                            router.post('/forms', { title: 'Untitled form' })
                        }
                    >
                        <Plus /> New form
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {statCards.map(({ key, label, icon: Icon, tone }) => (
                        <Card key={key} className="gap-4">
                            <CardHeader className="flex-row items-center justify-between">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {label}
                                </CardTitle>
                                <span className={`rounded-lg p-2 ${tone}`}>
                                    <Icon className="size-4" />
                                </span>
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold">
                                    {stats[key]}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-5">
                    <Card className="lg:col-span-3">
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Recently edited</CardTitle>
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/forms">
                                    View all <ArrowRight />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {recentForms.length === 0 && (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    Create a form to get started.
                                </p>
                            )}
                            {recentForms.map((form) => (
                                <Link
                                    key={form.uuid}
                                    href={`/forms/${form.uuid}/edit`}
                                    className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {form.title}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {form.submissions_count} responses �
                                            updated{' '}
                                            {new Date(
                                                form.updated_at,
                                            ).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            form.status === 'published'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {form.status}
                                    </Badge>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Latest responses</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentSubmissions.length === 0 && (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    No responses yet.
                                </p>
                            )}
                            {recentSubmissions.map((submission) => (
                                <Link
                                    key={submission.reference}
                                    href={`/forms/${submission.form.uuid}/submissions`}
                                    className="block rounded-lg border p-3 hover:bg-muted/50"
                                >
                                    <p className="truncate text-sm font-medium">
                                        {submission.form.title}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {new Date(
                                            submission.submitted_at,
                                        ).toLocaleString()}
                                    </p>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
