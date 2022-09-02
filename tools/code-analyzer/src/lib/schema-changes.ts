export type SchemaDiff = {
	[ key: string ]: {
		description: string;
		base: string;
		compare: string;
		method: string;
		areEqual: boolean;
	};
};

export const scanForSchemaChanges = ( schemaDiff: SchemaDiff ) => {
	const diff: Record< string, string > = {};

	Object.keys( schemaDiff ).forEach( ( key ) => {
		if ( ! schemaDiff[ key ].areEqual ) {
			diff[ key ] = schemaDiff[ key ].method;
		}
	} );

	return diff;
};
