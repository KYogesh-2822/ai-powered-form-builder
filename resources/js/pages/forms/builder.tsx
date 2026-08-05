import {
    DndContext,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlignLeft,
    CalendarDays,
    CheckSquare,
    ChevronDown,
    CircleDot,
    Copy,
    ExternalLink,
    FileUp,
    GripVertical,
    Hash,
    Heading2,
    Link2,
    Mail,
    MessageSquareText,
    Phone,
    Plus,
    Save,
    Star,
    Trash2,
    Type,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { FieldType, FormField, FormSchema, FormSection } from '@/types';

type FormData = {
    uuid: string;
    title: string;
    slug: string;
    description: string | null;
    status: 'draft' | 'published';
    schema: FormSchema;
    version: number;
    public_url: string;
    submissions_count: number;
};

type Props = { form: FormData; fieldTypes: FieldType[] };

const palette: { type: FieldType; label: string; icon: typeof Type }[] = [
    { type: 'text', label: 'Short text', icon: Type },
    { type: 'textarea', label: 'Long text', icon: AlignLeft },
    { type: 'number', label: 'Number', icon: Hash },
    { type: 'email', label: 'Email', icon: Mail },
    { type: 'phone', label: 'Phone', icon: Phone },
    { type: 'date', label: 'Date', icon: CalendarDays },
    { type: 'dropdown', label: 'Dropdown', icon: ChevronDown },
    { type: 'radio', label: 'Single choice', icon: CircleDot },
    { type: 'checkbox', label: 'Checkbox', icon: CheckSquare },
    { type: 'file', label: 'File upload', icon: FileUp },
    { type: 'heading', label: 'Heading', icon: Heading2 },
    { type: 'rating', label: 'Rating', icon: Star },
    { type: 'url', label: 'Website', icon: Link2 },
];

function fieldKey(label: string) {
    const base =
        label
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '') || 'field';

    return `${base}_${Math.random().toString(36).slice(2, 6)}`;
}

function makeField(type: FieldType): FormField {
    const label = palette.find((item) => item.type === type)?.label ?? 'Field';

    return {
        id: crypto.randomUUID(),
        type,
        label,
        key: type === 'heading' ? '' : fieldKey(label),
        placeholder: '',
        helpText: '',
        defaultValue: type === 'checkbox' ? false : '',
        required: false,
        options: ['dropdown', 'radio'].includes(type)
            ? [{ label: 'Option 1', value: 'option_1' }]
            : [],
        validation: {
            min: null,
            max: null,
            minLength: null,
            maxLength: null,
            regex: '',
            fileTypes: [],
            maxFileSize: 5120,
        },
        condition: null,
    };
}

function SortableField({
    field,
    selected,
    onSelect,
    onChange,
    onDuplicate,
    onDelete,
}: {
    field: FormField;
    selected: boolean;
    onSelect: () => void;
    onChange: (patch: Partial<FormField>) => void;
    onDuplicate: () => void;
    onDelete: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: field.id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            onClick={onSelect}
            className={`group rounded-xl border bg-background p-4 transition ${selected ? 'border-violet-500 ring-2 ring-violet-500/15' : 'hover:border-violet-300'}`}
        >
            <div className="flex items-start gap-2">
                <button
                    type="button"
                    {...attributes}
                    {...listeners}
                    className="mt-2 cursor-grab text-muted-foreground active:cursor-grabbing"
                >
                    <GripVertical className="size-4" />
                </button>
                <div className="min-w-0 flex-1">
                    <Input
                        value={field.label}
                        onChange={(event) =>
                            onChange({ label: event.target.value })
                        }
                        onClick={(event) => event.stopPropagation()}
                        className="h-auto border-0 px-0 py-1 text-sm font-semibold shadow-none focus-visible:ring-0"
                    />
                    <p className="mt-1 text-xs text-muted-foreground">
                        {field.type}
                        {field.required ? ' - required' : ''}
                    </p>
                </div>
                <div className="flex opacity-0 transition-opacity group-hover:opacity-100">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={(event) => {
                            event.stopPropagation();
                            onDuplicate();
                        }}
                    >
                        <Copy />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={(event) => {
                            event.stopPropagation();
                            onDelete();
                        }}
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>
            {field.type !== 'heading' && (
                <div className="mt-3 ml-6 rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                    {field.placeholder ||
                        (['dropdown', 'radio'].includes(field.type)
                            ? field.options
                                  .map((option) => option.label)
                                  .join(' / ')
                            : `Enter ${field.label.toLowerCase()}`)}
                </div>
            )}
        </div>
    );
}

export default function FormBuilder({ form }: Props) {
    const [title, setTitle] = useState(form.title);
    const [description, setDescription] = useState(form.description ?? '');
    const [schema, setSchema] = useState<FormSchema>(form.schema);
    const [selected, setSelected] = useState<{
        sectionId: string;
        fieldId: string;
    } | null>(null);
    const [mode, setMode] = useState<'canvas' | 'json'>('canvas');
    const [rawJson, setRawJson] = useState(
        JSON.stringify(form.schema, null, 2),
    );
    const [jsonError, setJsonError] = useState('');
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    );

    const selectedField = useMemo(() => {
        if (!selected) {
            return null;
        }

        return (
            schema.sections
                .find((section) => section.id === selected.sectionId)
                ?.fields.find((field) => field.id === selected.fieldId) ?? null
        );
    }, [schema, selected]);

    function mutateSection(
        sectionId: string,
        callback: (section: FormSection) => FormSection,
    ) {
        setSchema((current) => ({
            ...current,
            sections: current.sections.map((section) =>
                section.id === sectionId ? callback(section) : section,
            ),
        }));
    }

    function addField(type: FieldType, sectionId = schema.sections[0]?.id) {
        if (!sectionId) {
            return;
        }

        const field = makeField(type);
        mutateSection(sectionId, (section) => ({
            ...section,
            fields: [...section.fields, field],
        }));
        setSelected({ sectionId, fieldId: field.id });
    }

    function updateField(
        sectionId: string,
        fieldId: string,
        patch: Partial<FormField>,
    ) {
        mutateSection(sectionId, (section) => ({
            ...section,
            fields: section.fields.map((field) =>
                field.id === fieldId ? { ...field, ...patch } : field,
            ),
        }));
    }

    function deleteField(sectionId: string, fieldId: string) {
        mutateSection(sectionId, (section) => ({
            ...section,
            fields: section.fields.filter((field) => field.id !== fieldId),
        }));

        if (selected?.fieldId === fieldId) {
            setSelected(null);
        }
    }

    function duplicateField(sectionId: string, field: FormField) {
        const copy = {
            ...field,
            id: crypto.randomUUID(),
            key: field.type === 'heading' ? '' : fieldKey(field.label),
            label: `${field.label} copy`,
        };
        mutateSection(sectionId, (section) => {
            const index = section.fields.findIndex(
                (item) => item.id === field.id,
            );
            const fields = [...section.fields];
            fields.splice(index + 1, 0, copy);

            return { ...section, fields };
        });
        setSelected({ sectionId, fieldId: copy.id });
    }

    function addSection() {
        setSchema((current) => ({
            ...current,
            sections: [
                ...current.sections,
                {
                    id: crypto.randomUUID(),
                    title: 'New section',
                    description: '',
                    fields: [],
                },
            ],
        }));
    }

    function handleDragEnd(sectionId: string, event: DragEndEvent) {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        mutateSection(sectionId, (section) => {
            const from = section.fields.findIndex(
                (field) => field.id === active.id,
            );
            const to = section.fields.findIndex(
                (field) => field.id === over.id,
            );

            return { ...section, fields: arrayMove(section.fields, from, to) };
        });
    }

    function save() {
        router.put(
            `/forms/${form.uuid}`,
            { title, description, schema },
            {
                preserveScroll: true,
                onSuccess: () => setRawJson(JSON.stringify(schema, null, 2)),
            },
        );
    }

    function applyJson() {
        try {
            const parsed = JSON.parse(rawJson) as FormSchema;

            if (!Array.isArray(parsed.sections)) {
                throw new Error('Schema must contain a sections array.');
            }

            setSchema(parsed);
            setJsonError('');
            setMode('canvas');
        } catch (error) {
            setJsonError(
                error instanceof Error ? error.message : 'Invalid JSON.',
            );
        }
    }

    return (
        <>
            <Head title={title} />
            <div className="flex min-h-[calc(100vh-4rem)] flex-1 flex-col overflow-hidden">
                <header className="flex flex-wrap items-center gap-3 border-b bg-background px-4 py-3">
                    <div className="min-w-52 flex-1">
                        <Input
                            value={title}
                            onChange={(event) => setTitle(event.target.value)}
                            className="h-8 max-w-xl border-0 px-0 text-lg font-bold shadow-none focus-visible:ring-0"
                        />
                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                            <Badge
                                variant={
                                    form.status === 'published'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {form.status}
                            </Badge>
                            <span>v{form.version}</span>
                            <span>{form.submissions_count} responses</span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={`/forms/${form.uuid}/submissions`}>
                                <MessageSquareText /> Responses
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            disabled={form.status !== 'published'}
                        >
                            <a
                                href={form.public_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink /> Preview
                            </a>
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.patch(`/forms/${form.uuid}/publish`)
                            }
                        >
                            {form.status === 'published'
                                ? 'Unpublish'
                                : 'Publish'}
                        </Button>
                        <Button onClick={save}>
                            <Save /> Save
                        </Button>
                    </div>
                </header>

                <div className="flex items-center gap-2 border-b bg-muted/30 px-4 py-2">
                    <Button
                        size="sm"
                        variant={mode === 'canvas' ? 'default' : 'ghost'}
                        onClick={() => setMode('canvas')}
                    >
                        Canvas
                    </Button>
                    <Button
                        size="sm"
                        variant={mode === 'json' ? 'default' : 'ghost'}
                        onClick={() => {
                            setRawJson(JSON.stringify(schema, null, 2));
                            setMode('json');
                        }}
                    >
                        JSON schema
                    </Button>
                    <Input
                        value={description}
                        onChange={(event) => setDescription(event.target.value)}
                        placeholder="Form description"
                        className="ml-auto max-w-md bg-background"
                    />
                </div>

                {mode === 'json' ? (
                    <div className="flex flex-1 flex-col gap-3 p-4">
                        <textarea
                            value={rawJson}
                            onChange={(event) => setRawJson(event.target.value)}
                            className="min-h-[65vh] flex-1 rounded-xl border bg-slate-950 p-5 font-mono text-sm text-slate-100 outline-none focus:ring-2 focus:ring-violet-500"
                            spellCheck={false}
                        />
                        {jsonError && (
                            <p className="text-sm text-destructive">
                                {jsonError}
                            </p>
                        )}
                        <div className="flex justify-end">
                            <Button onClick={applyJson}>
                                Validate and apply JSON
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div className="grid flex-1 grid-cols-1 overflow-hidden xl:grid-cols-[220px_minmax(480px,1fr)_320px]">
                        <aside className="hidden overflow-y-auto border-r bg-muted/20 p-4 xl:block">
                            <p className="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Fields
                            </p>
                            <div className="grid grid-cols-2 gap-2">
                                {palette.map(({ type, label, icon: Icon }) => (
                                    <button
                                        key={type}
                                        type="button"
                                        draggable
                                        onDragStart={(event) =>
                                            event.dataTransfer.setData(
                                                'field-type',
                                                type,
                                            )
                                        }
                                        onClick={() => addField(type)}
                                        className="flex flex-col items-center gap-2 rounded-lg border bg-background p-3 text-center text-xs font-medium transition hover:border-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/30"
                                    >
                                        <Icon className="size-4 text-violet-600" />
                                        {label}
                                    </button>
                                ))}
                            </div>
                            <Button
                                variant="outline"
                                className="mt-4 w-full"
                                onClick={addSection}
                            >
                                <Plus /> Add section
                            </Button>
                        </aside>

                        <main className="overflow-y-auto bg-muted/20 p-4 md:p-8">
                            <div className="mx-auto max-w-3xl space-y-6">
                                {schema.sections.map(
                                    (section, sectionIndex) => (
                                        <Card
                                            key={section.id}
                                            onDragOver={(event) =>
                                                event.preventDefault()
                                            }
                                            onDrop={(event) => {
                                                const type =
                                                    event.dataTransfer.getData(
                                                        'field-type',
                                                    ) as FieldType;

                                                if (type) {
                                                    addField(type, section.id);
                                                }
                                            }}
                                        >
                                            <CardHeader>
                                                <div className="flex gap-3">
                                                    <span className="mt-2 flex size-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">
                                                        {sectionIndex + 1}
                                                    </span>
                                                    <div className="flex-1">
                                                        <Input
                                                            value={
                                                                section.title
                                                            }
                                                            onChange={(event) =>
                                                                mutateSection(
                                                                    section.id,
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        title: event
                                                                            .target
                                                                            .value,
                                                                    }),
                                                                )
                                                            }
                                                            className="border-0 px-0 text-lg font-bold shadow-none focus-visible:ring-0"
                                                        />
                                                        <Input
                                                            value={
                                                                section.description
                                                            }
                                                            onChange={(event) =>
                                                                mutateSection(
                                                                    section.id,
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        description:
                                                                            event
                                                                                .target
                                                                                .value,
                                                                    }),
                                                                )
                                                            }
                                                            placeholder="Section description"
                                                            className="border-0 px-0 text-sm shadow-none focus-visible:ring-0"
                                                        />
                                                    </div>
                                                </div>
                                            </CardHeader>
                                            <CardContent>
                                                <DndContext
                                                    sensors={sensors}
                                                    onDragEnd={(event) =>
                                                        handleDragEnd(
                                                            section.id,
                                                            event,
                                                        )
                                                    }
                                                >
                                                    <SortableContext
                                                        items={section.fields.map(
                                                            (field) => field.id,
                                                        )}
                                                        strategy={
                                                            verticalListSortingStrategy
                                                        }
                                                    >
                                                        <div className="space-y-3">
                                                            {section.fields.map(
                                                                (field) => (
                                                                    <SortableField
                                                                        key={
                                                                            field.id
                                                                        }
                                                                        field={
                                                                            field
                                                                        }
                                                                        selected={
                                                                            selected?.fieldId ===
                                                                            field.id
                                                                        }
                                                                        onSelect={() =>
                                                                            setSelected(
                                                                                {
                                                                                    sectionId:
                                                                                        section.id,
                                                                                    fieldId:
                                                                                        field.id,
                                                                                },
                                                                            )
                                                                        }
                                                                        onChange={(
                                                                            patch,
                                                                        ) =>
                                                                            updateField(
                                                                                section.id,
                                                                                field.id,
                                                                                patch,
                                                                            )
                                                                        }
                                                                        onDuplicate={() =>
                                                                            duplicateField(
                                                                                section.id,
                                                                                field,
                                                                            )
                                                                        }
                                                                        onDelete={() =>
                                                                            deleteField(
                                                                                section.id,
                                                                                field.id,
                                                                            )
                                                                        }
                                                                    />
                                                                ),
                                                            )}
                                                            {section.fields
                                                                .length ===
                                                                0 && (
                                                                <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                                                    Drop a field
                                                                    here or
                                                                    select one
                                                                    from the
                                                                    palette.
                                                                </div>
                                                            )}
                                                        </div>
                                                    </SortableContext>
                                                </DndContext>
                                            </CardContent>
                                        </Card>
                                    ),
                                )}
                                <Button
                                    variant="outline"
                                    className="w-full border-dashed"
                                    onClick={addSection}
                                >
                                    <Plus /> Add another section
                                </Button>
                            </div>
                        </main>

                        <FieldInspector
                            field={selectedField}
                            onChange={(patch) =>
                                selected &&
                                updateField(
                                    selected.sectionId,
                                    selected.fieldId,
                                    patch,
                                )
                            }
                        />
                    </div>
                )}
            </div>
        </>
    );
}

function FieldInspector({
    field,
    onChange,
}: {
    field: FormField | null;
    onChange: (patch: Partial<FormField>) => void;
}) {
    if (!field) {
        return (
            <aside className="hidden border-l bg-background p-5 xl:block">
                <p className="text-sm font-semibold">Field settings</p>
                <p className="mt-2 text-sm text-muted-foreground">
                    Select a field to configure it.
                </p>
            </aside>
        );
    }

    const choice = ['dropdown', 'radio'].includes(field.type);

    return (
        <aside className="hidden overflow-y-auto border-l bg-background p-5 xl:block">
            <p className="text-sm font-semibold">Field settings</p>
            <div className="mt-5 space-y-4">
                <Setting label="Label">
                    <Input
                        value={field.label}
                        onChange={(e) => onChange({ label: e.target.value })}
                    />
                </Setting>
                {field.type !== 'heading' && (
                    <Setting label="Key">
                        <Input
                            value={field.key}
                            onChange={(e) =>
                                onChange({
                                    key: e.target.value
                                        .toLowerCase()
                                        .replace(/[^a-z0-9_]/g, '_'),
                                })
                            }
                        />
                    </Setting>
                )}
                <Setting label="Placeholder">
                    <Input
                        value={field.placeholder}
                        onChange={(e) =>
                            onChange({ placeholder: e.target.value })
                        }
                    />
                </Setting>
                <Setting label="Help text">
                    <Input
                        value={field.helpText}
                        onChange={(e) => onChange({ helpText: e.target.value })}
                    />
                </Setting>
                {field.type !== 'heading' && (
                    <label className="flex items-center justify-between rounded-lg border p-3 text-sm font-medium">
                        Required
                        <input
                            type="checkbox"
                            checked={field.required}
                            onChange={(e) =>
                                onChange({ required: e.target.checked })
                            }
                            className="size-4 accent-violet-600"
                        />
                    </label>
                )}
                {choice && (
                    <Setting label="Options (one per line)">
                        <textarea
                            value={field.options
                                .map(
                                    (option) =>
                                        `${option.label}|${option.value}`,
                                )
                                .join('\n')}
                            onChange={(e) =>
                                onChange({
                                    options: e.target.value
                                        .split('\n')
                                        .filter(Boolean)
                                        .map((line) => {
                                            const [label, value] =
                                                line.split('|');

                                            return {
                                                label: label.trim(),
                                                value: (value ?? label)
                                                    .trim()
                                                    .toLowerCase()
                                                    .replace(/\s+/g, '_'),
                                            };
                                        }),
                                })
                            }
                            className="min-h-32 w-full rounded-md border bg-transparent p-3 text-sm"
                        />
                    </Setting>
                )}
                {['number', 'rating'].includes(field.type) && (
                    <div className="grid grid-cols-2 gap-2">
                        <Setting label="Minimum">
                            <Input
                                type="number"
                                value={field.validation.min ?? ''}
                                onChange={(e) =>
                                    onChange({
                                        validation: {
                                            ...field.validation,
                                            min:
                                                e.target.value === ''
                                                    ? null
                                                    : Number(e.target.value),
                                        },
                                    })
                                }
                            />
                        </Setting>
                        <Setting label="Maximum">
                            <Input
                                type="number"
                                value={field.validation.max ?? ''}
                                onChange={(e) =>
                                    onChange({
                                        validation: {
                                            ...field.validation,
                                            max:
                                                e.target.value === ''
                                                    ? null
                                                    : Number(e.target.value),
                                        },
                                    })
                                }
                            />
                        </Setting>
                    </div>
                )}
                {!['number', 'rating', 'file', 'heading'].includes(
                    field.type,
                ) && (
                    <div className="grid grid-cols-2 gap-2">
                        <Setting label="Min length">
                            <Input
                                type="number"
                                value={field.validation.minLength ?? ''}
                                onChange={(e) =>
                                    onChange({
                                        validation: {
                                            ...field.validation,
                                            minLength:
                                                e.target.value === ''
                                                    ? null
                                                    : Number(e.target.value),
                                        },
                                    })
                                }
                            />
                        </Setting>
                        <Setting label="Max length">
                            <Input
                                type="number"
                                value={field.validation.maxLength ?? ''}
                                onChange={(e) =>
                                    onChange({
                                        validation: {
                                            ...field.validation,
                                            maxLength:
                                                e.target.value === ''
                                                    ? null
                                                    : Number(e.target.value),
                                        },
                                    })
                                }
                            />
                        </Setting>
                    </div>
                )}
                {field.type === 'file' && (
                    <>
                        <Setting label="Allowed extensions">
                            <Input
                                value={field.validation.fileTypes.join(',')}
                                onChange={(e) =>
                                    onChange({
                                        validation: {
                                            ...field.validation,
                                            fileTypes: e.target.value
                                                .split(',')
                                                .map((value) => value.trim())
                                                .filter(Boolean),
                                        },
                                    })
                                }
                                placeholder="pdf,docx,jpg"
                            />
                        </Setting>
                        <Setting label="Maximum size (KB)">
                            <Input
                                type="number"
                                value={field.validation.maxFileSize}
                                onChange={(e) =>
                                    onChange({
                                        validation: {
                                            ...field.validation,
                                            maxFileSize: Number(e.target.value),
                                        },
                                    })
                                }
                            />
                        </Setting>
                    </>
                )}
                {!['file', 'heading'].includes(field.type) && (
                    <Setting label="Regex rule">
                        <Input
                            value={field.validation.regex}
                            onChange={(e) =>
                                onChange({
                                    validation: {
                                        ...field.validation,
                                        regex: e.target.value,
                                    },
                                })
                            }
                            placeholder="/^[A-Z]/"
                        />
                    </Setting>
                )}
            </div>
        </aside>
    );
}

function Setting({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <label className="block space-y-1.5 text-xs font-medium text-muted-foreground">
            <span>{label}</span>
            {children}
        </label>
    );
}
