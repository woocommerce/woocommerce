import path from 'path';
import ts from 'typescript';

it( 'preserves core-data selector and action types with bundler resolution', () => {
	const fileName = path.join( __dirname, 'wordpress-data-consumer.ts' );
	const source = `
		import { store } from '@wordpress/core-data';
		import { dispatch, select } from '@wordpress/data';

		type IsAny<T> = 0 extends (1 & T) ? true : false;
		const selectors = select(store);
		const actions = dispatch(store);
		const selectorsAreTyped: false = null as unknown as IsAny<typeof selectors>;
		const actionsAreTyped: false = null as unknown as IsAny<typeof actions>;
		const getRecordIsTyped: false = null as unknown as IsAny<typeof selectors.getEntityRecord>;
		const editRecordIsTyped: false = null as unknown as IsAny<typeof actions.editEntityRecord>;

		selectors.getEntityRecord('root', 'site');
		actions.editEntityRecord('postType', 'page', 1, { title: 'Store' });

		// @ts-expect-error Entity selectors require a kind and name.
		selectors.getEntityRecord();
		// @ts-expect-error Entity actions require a kind and name.
		actions.editEntityRecord();
	`;
	const options = {
		target: ts.ScriptTarget.ESNext,
		module: ts.ModuleKind.ESNext,
		moduleResolution: ts.ModuleResolutionKind.Bundler,
		strict: true,
		noEmit: true,
		skipLibCheck: true,
		types: [],
	};
	const host = ts.createCompilerHost( options );
	const getSourceFile = host.getSourceFile.bind( host );
	host.getSourceFile = ( requestedFile, ...args ) =>
		requestedFile === fileName
			? ts.createSourceFile( fileName, source, options.target, true )
			: getSourceFile( requestedFile, ...args );

	const program = ts.createProgram( [ fileName ], options, host );
	const diagnostics = ts.getPreEmitDiagnostics( program );

	expect(
		diagnostics.map( ( diagnostic ) =>
			ts.flattenDiagnosticMessageText( diagnostic.messageText, '\n' )
		)
	).toEqual( [] );
} );
