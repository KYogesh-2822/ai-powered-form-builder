import { router } from '@inertiajs/react';
import { History, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';

export type FormVersionSummary = {
    id: number;
    version: number;
    source: string;
    summary: string;
    created_at: string;
    user: { id: number; name: string } | null;
};

export default function FormVersionHistory({
    formUuid,
    currentVersion,
    versions,
}: {
    formUuid: string;
    currentVersion: number;
    versions: FormVersionSummary[];
}) {
    function restore(version: FormVersionSummary) {
        if (
            !window.confirm(
                `Restore version ${version.version}? Your current form will be preserved as version ${currentVersion}.`,
            )
        ) {
            return;
        }

        router.post(`/forms/${formUuid}/versions/${version.id}/restore`);
    }

    return (
        <div className="border-b bg-slate-50 px-4 py-3 dark:bg-slate-950/40">
            <div className="mx-auto flex max-w-7xl items-start gap-3 overflow-x-auto pb-1">
                <div className="flex min-w-48 items-center gap-2 py-2 text-sm font-semibold">
                    <History className="size-4 text-violet-600" />
                    Version history
                </div>
                {versions.map((version) => {
                    const current = version.version === currentVersion;

                    return (
                        <div
                            key={version.id}
                            className={`min-w-64 rounded-lg border bg-background p-3 ${current ? 'border-violet-400' : ''}`}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm font-semibold">
                                    Version {version.version}
                                </span>
                                <span className="rounded bg-muted px-2 py-0.5 text-[10px] font-medium uppercase">
                                    {version.source}
                                </span>
                            </div>
                            <p className="mt-1 truncate text-xs text-muted-foreground">
                                {version.summary}
                            </p>
                            <div className="mt-2 flex items-center justify-between gap-2">
                                <span className="text-[11px] text-muted-foreground">
                                    {new Date(
                                        version.created_at,
                                    ).toLocaleString()}
                                </span>
                                {!current && (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        className="h-7"
                                        onClick={() => restore(version)}
                                    >
                                        <RotateCcw /> Restore
                                    </Button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
