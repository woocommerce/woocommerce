export type Property = {
    type: string;
}

export type Schema = {
    $schema: string;
    title?: string;
    type: string;
    properties: Property[];
}

export type Data = {
    [key: string]: unknown;
};