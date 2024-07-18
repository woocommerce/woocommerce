export type Property = {
    type: string;
}

export type ObjectSchema = Property & {
    $schema?: string;
    title?: string;
    properties: {
        [key: string] : Property
    };
}

export type StringSchema = Property & {
    minLength?: number;
    maxLength?: number;
    pattern?: string;
    format?: string;
}

export type Data = {
    [key: string]: unknown;
};

export type ValidationError = {
    code: string;
    keyword: string;
    message: string;
    path: string;
}