export type Property = {
    type: string;
    [ key: string ]: any;
}

export type ObjectSchema = Property & {
    $schema?: string;
    title?: string;
    properties: {
        [key: string] : Property
    };
    required?: string[];
}

export type DataRef = {
    $data: string;
}

export type StringSchema = Property & {
    minLength?: string | DataRef;
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
    ( datum: DataType, path: string, data: Data ): ValidationError[];
}
