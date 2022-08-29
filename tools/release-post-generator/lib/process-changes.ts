type AnalyzerChangeset = {
	hooks: [ string[] ];
	schema: Record< string, string >;
	db: { functionVersion: string; functionName: string };
	templates: Record< string, string >;
};

export const processChanges = ( changes: AnalyzerChangeset ) => {
	const hooks = Object.entries( changes.hooks ).map( ( [ key, val ] ) => {
		return { name: key, description: val[ 0 ][ 1 ][ 2 ] };
	} );

	const schema = Object.entries( changes.schema ).map( ( [ key, val ] ) => {
		return { className: key, codeRef: val };
	} );

	const db = changes.db;

	const templates = Object.keys( changes.templates );

	return { hooks, schema, db, templates };
};
