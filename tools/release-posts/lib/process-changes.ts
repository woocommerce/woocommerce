type AnalyzerChangeset = {
	hooks: [ string[] ];
	schema: Record< string, string >;
	db: { functionVersion: string; functionName: string };
	templates: Record< string, string >;
};

export const processChanges = ( changes: AnalyzerChangeset ) => {
	const hooks = Object.entries( changes.hooks ).map( ( [ , val ] ) => {
		return {
			name: val[ 0 ][ 0 ],
			description: val[ 0 ][ 1 ][ 3 ],
		};
	} );

	const schema = Object.entries( changes.schema ).map( ( [ key, val ] ) => {
		return { className: key, codeRef: val };
	} );

	const db = changes.db;

	const templates = Object.keys( changes.templates );

	return { hooks, schema, db, templates };
};
