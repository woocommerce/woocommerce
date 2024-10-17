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

export type Context< SchemaType > = {
    schema: SchemaType,
    value: unknown,
    path: string,
    data: Data,
    filters: Validator[],
}

export type ParsedContext< SchemaType, ParsedType > = Context< SchemaType > & {
    parsed: ParsedType
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


export interface Validator {
    ( context: ParsedContext< any, any > ): ValidationError[];
}

export interface KeywordInterface< SchemaType, DataType > {
    ( context: ParsedContext< SchemaType, DataType >, operand: Data ): ValidationError[];
};
