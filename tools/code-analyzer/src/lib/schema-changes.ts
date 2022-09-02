export type SchemaDiff = {
	[ key: string ]: {
		description: string;
		base: string;
		compare: string;
		method: string;
		areEqual: boolean;
	};
} | null;

export const scanForSchemaChanges = ( schemaDiff: SchemaDiff ) => {};
