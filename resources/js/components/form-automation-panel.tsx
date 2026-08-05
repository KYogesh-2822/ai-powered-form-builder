import { router } from '@inertiajs/react';
import {
    CheckCircle2,
    FileSpreadsheet,
    FileText,
    LoaderCircle,
    Sparkles,
    WandSparkles,
    XCircle,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { FieldType, FormSchema } from '@/types';

export type FormAutomation = {
    uuid: string;
    type: 'ai_generate' | 'ai_edit' | 'import';
    status: 'queued' | 'running' | 'completed' | 'failed';
    input: { original_name?: string; prompt?: string };
    result_schema: FormSchema | null;
    provider: string | null;
    model: string | null;
    input_tokens: number | null;
    output_tokens: number | null;
    latency_ms: number | null;
    error: string | null;
    created_at: string;
    completed_at: string | null;
    applied_at: string | null;
};

type Props = {
    formUuid: string;
    automation: FormAutomation | null;
};

const importFieldTypes: FieldType[] = [
    'text',
    'textarea',
    'number',
    'email',
    'phone',
    'date',
    'dropdown',
    'radio',
    'checkbox',
    'file',
    'heading',
    'rating',
    'url',
];

export default function FormAutomationPanel({ formUuid, automation }: Props) {
    const [prompt, setPrompt] = useState('');
    const [mode, setMode] = useState<'generate' | 'edit'>('generate');
    const [processing, setProcessing] = useState(false);
    const [mappedSchema, setMappedSchema] = useState<FormSchema | null>(() =>
        automation?.type === 'import' && automation.status === 'completed'
            ? automation.result_schema
            : null,
    );

    useEffect(() => {
        if (!automation || !['queued', 'running'].includes(automation.status)) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({
                only: ['form', 'latestAutomation'],
            });
        }, 2500);

        return () => window.clearInterval(timer);
    }, [automation]);

    const preview = useMemo(() => {
        if (!automation?.result_schema) {
            return null;
        }

        return {
            sections: automation.result_schema.sections.length,
            fields: automation.result_schema.sections.reduce(
                (total, section) => total + section.fields.length,
                0,
            ),
            labels: automation.result_schema.sections
                .flatMap((section) => section.fields)
                .slice(0, 6)
                .map((field) => field.label),
        };
    }, [automation]);

    function submitAi() {
        if (prompt.trim().length < 10) {
            return;
        }

        setProcessing(true);
        router.post(
            `/forms/${formUuid}/ai`,
            { mode, prompt },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
                onSuccess: () => setPrompt(''),
            },
        );
    }

    function uploadDocument(file: File | undefined) {
        if (!file) {
            return;
        }

        const data = new FormData();
        data.append('document', file);
        setProcessing(true);
        router.post(`/forms/${formUuid}/imports`, data, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    }

    function applyImport() {
        if (!automation || !mappedSchema) {
            return;
        }

        setProcessing(true);
        router.post(
            `/form-automations/${automation.uuid}/apply`,
            { schema: mappedSchema },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    }

    function updateMappedType(
        sectionId: string,
        fieldId: string,
        type: FieldType,
    ) {
        setMappedSchema((current) => {
            if (!current) {
                return current;
            }

            return {
                ...current,
                sections: current.sections.map((section) =>
                    section.id !== sectionId
                        ? section
                        : {
                              ...section,
                              fields: section.fields.map((field) => {
                                  if (field.id !== fieldId) {
                                      return field;
                                  }

                                  const needsOptions = [
                                      'dropdown',
                                      'radio',
                                  ].includes(type);

                                  return {
                                      ...field,
                                      type,
                                      key:
                                          type === 'heading'
                                              ? ''
                                              : field.key ||
                                                `imported_${field.id
                                                    .replace(/[^a-z0-9]/gi, '')
                                                    .slice(0, 12)
                                                    .toLowerCase()}`,
                                      defaultValue:
                                          type === 'checkbox'
                                              ? false
                                              : field.defaultValue,
                                      options:
                                          needsOptions &&
                                          field.options.length === 0
                                              ? [
                                                    {
                                                        label: 'Option 1',
                                                        value: 'option_1',
                                                    },
                                                ]
                                              : field.options,
                                  };
                              }),
                          },
                ),
            };
        });
    }

    return (
        <div className="grid gap-4 border-b bg-violet-50/60 p-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)] dark:bg-violet-950/10">
            <Card className="border-violet-200 dark:border-violet-900">
                <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2 font-semibold">
                                <WandSparkles className="size-4 text-violet-600" />
                                Build with AI
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Generate a complete form or describe a targeted
                                edit to the current schema.
                            </p>
                        </div>
                        <div className="flex rounded-lg border bg-background p-1">
                            {(['generate', 'edit'] as const).map((item) => (
                                <button
                                    key={item}
                                    type="button"
                                    onClick={() => setMode(item)}
                                    className={`rounded-md px-3 py-1 text-xs font-medium capitalize ${mode === item ? 'bg-violet-600 text-white' : 'text-muted-foreground'}`}
                                >
                                    {item}
                                </button>
                            ))}
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    <textarea
                        value={prompt}
                        onChange={(event) => setPrompt(event.target.value)}
                        placeholder={
                            mode === 'generate'
                                ? 'Create a customer onboarding form with contact details, business profile, and consent...'
                                : 'Add a required budget field and move contact details into their own section...'
                        }
                        className="min-h-24 w-full resize-y rounded-lg border bg-background p-3 text-sm outline-none focus:ring-2 focus:ring-violet-500"
                        maxLength={4000}
                    />
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-xs text-muted-foreground">
                            Generated output is validated against the same
                            server-side schema as manual changes.
                        </p>
                        <Button
                            type="button"
                            onClick={submitAi}
                            disabled={processing || prompt.trim().length < 10}
                        >
                            {processing ? (
                                <LoaderCircle className="animate-spin" />
                            ) : (
                                <Sparkles />
                            )}
                            Run AI
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-3">
                    <div className="flex items-center gap-2 font-semibold">
                        <FileSpreadsheet className="size-4 text-emerald-600" />
                        Import a document
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Upload DOCX or XLSX, up to 10 MB. Processing happens in
                        the background.
                    </p>
                </CardHeader>
                <CardContent>
                    <label className="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed bg-muted/30 p-5 text-sm font-medium transition hover:border-violet-400">
                        <FileText className="size-4" />
                        Choose Word or Excel file
                        <input
                            type="file"
                            accept=".docx,.xlsx"
                            className="sr-only"
                            disabled={processing}
                            onChange={(event) => {
                                uploadDocument(event.target.files?.[0]);
                                event.target.value = '';
                            }}
                        />
                    </label>

                    {automation && (
                        <div className="mt-4 rounded-lg border bg-background p-3 text-sm">
                            <div className="flex items-center justify-between gap-2">
                                <span className="truncate font-medium">
                                    {automation.type === 'import'
                                        ? automation.input.original_name ||
                                          'Document import'
                                        : automation.type === 'ai_edit'
                                          ? 'AI form edit'
                                          : 'AI form generation'}
                                </span>
                                <StatusBadge status={automation.status} />
                            </div>

                            {['queued', 'running'].includes(
                                automation.status,
                            ) && (
                                <p className="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                                    <LoaderCircle className="size-3 animate-spin" />
                                    Background processing in progress...
                                </p>
                            )}
                            {automation.error && (
                                <p className="mt-2 text-xs text-destructive">
                                    {automation.error}
                                </p>
                            )}
                            {preview && automation.status === 'completed' && (
                                <div className="mt-3 space-y-2">
                                    <p className="text-xs text-muted-foreground">
                                        {preview.sections} sections,{' '}
                                        {preview.fields} fields
                                        {automation.latency_ms
                                            ? ` - ${automation.latency_ms} ms`
                                            : ''}
                                    </p>
                                    <p className="line-clamp-2 text-xs">
                                        {preview.labels.join(', ')}
                                    </p>
                                    {automation.type === 'import' &&
                                        !automation.applied_at &&
                                        mappedSchema && (
                                            <div className="space-y-2">
                                                <p className="pt-1 text-xs font-semibold">
                                                    Review field mapping
                                                </p>
                                                <div className="max-h-52 space-y-1 overflow-y-auto pr-1">
                                                    {mappedSchema.sections.flatMap(
                                                        (section) =>
                                                            section.fields.map(
                                                                (field) => (
                                                                    <div
                                                                        key={
                                                                            field.id
                                                                        }
                                                                        className="grid grid-cols-[minmax(0,1fr)_110px] items-center gap-2 rounded-md bg-muted/50 p-2"
                                                                    >
                                                                        <span className="truncate text-xs">
                                                                            {
                                                                                field.label
                                                                            }
                                                                        </span>
                                                                        <select
                                                                            value={
                                                                                field.type
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updateMappedType(
                                                                                    section.id,
                                                                                    field.id,
                                                                                    event
                                                                                        .target
                                                                                        .value as FieldType,
                                                                                )
                                                                            }
                                                                            className="rounded border bg-background px-2 py-1 text-xs"
                                                                        >
                                                                            {importFieldTypes.map(
                                                                                (
                                                                                    type,
                                                                                ) => (
                                                                                    <option
                                                                                        key={
                                                                                            type
                                                                                        }
                                                                                        value={
                                                                                            type
                                                                                        }
                                                                                    >
                                                                                        {
                                                                                            type
                                                                                        }
                                                                                    </option>
                                                                                ),
                                                                            )}
                                                                        </select>
                                                                    </div>
                                                                ),
                                                            ),
                                                    )}
                                                </div>
                                                <Button
                                                    size="sm"
                                                    className="w-full"
                                                    onClick={applyImport}
                                                    disabled={processing}
                                                >
                                                    Apply reviewed schema
                                                </Button>
                                            </div>
                                        )}
                                    {automation.applied_at && (
                                        <p className="flex items-center gap-1 text-xs font-medium text-emerald-700">
                                            <CheckCircle2 className="size-3" />
                                            Applied to the builder
                                        </p>
                                    )}
                                </div>
                            )}
                            {automation.model && (
                                <p className="mt-2 text-[11px] text-muted-foreground">
                                    {automation.model} -{' '}
                                    {(automation.input_tokens ?? 0) +
                                        (automation.output_tokens ?? 0)}{' '}
                                    tokens
                                </p>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

function StatusBadge({ status }: { status: FormAutomation['status'] }) {
    if (status === 'failed') {
        return (
            <Badge variant="destructive">
                <XCircle /> Failed
            </Badge>
        );
    }

    if (status === 'completed') {
        return (
            <Badge className="bg-emerald-600">
                <CheckCircle2 /> Ready
            </Badge>
        );
    }

    return <Badge variant="secondary">{status}</Badge>;
}
