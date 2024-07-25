export type Property = {
    type: string;
}

export type ObjectSchema = Property & {
    $schema?: string;
    title?: string;
    properties: {
        [key: string] : Property
    };
    required?: string[];
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

export interface KeywordInterface< DataType, SchemaType > {
    ( data: DataType, schema: SchemaType, path: string ): ValidationError[];
}
