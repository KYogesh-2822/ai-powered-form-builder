export type FieldType =
    | 'text'
    | 'textarea'
    | 'number'
    | 'email'
    | 'phone'
    | 'date'
    | 'dropdown'
    | 'radio'
    | 'checkbox'
    | 'file'
    | 'heading'
    | 'rating'
    | 'url';

export type FormOption = { label: string; value: string };

export type FormField = {
    id: string;
    type: FieldType;
    label: string;
    key: string;
    placeholder: string;
    helpText: string;
    defaultValue: string | number | boolean;
    required: boolean;
    options: FormOption[];
    validation: {
        min: number | null;
        max: number | null;
        minLength: number | null;
        maxLength: number | null;
        regex: string;
        fileTypes: string[];
        maxFileSize: number;
    };
    condition: {
        field: string;
        operator: 'equals' | 'not_equals' | 'contains';
        value: string;
    } | null;
};

export type FormSection = {
    id: string;
    title: string;
    description: string;
    fields: FormField[];
};

export type FormSchema = {
    version: number;
    settings: {
        submitLabel: string;
        successMessage: string;
        showProgress: boolean;
    };
    sections: FormSection[];
};

export type FormSummary = {
    uuid: string;
    title: string;
    slug: string;
    status: 'draft' | 'published' | 'archived';
    submissions_count: number;
    updated_at: string;
};

export type PublicFormSummary = {
    title: string;
    slug: string;
    description: string | null;
    public_url: string;
    sections_count: number;
    fields_count: number;
    published_at: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};
