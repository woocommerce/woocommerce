/**
 * External dependencies
 */
import { readFileSync, readdirSync, statSync } from 'fs';
import { join } from 'path';
import * as ts from 'typescript';

/**
 * Internal dependencies
 */
import { storeName } from '../store/constants';

/**
 * Pins the parts of this package that MailPoet's JavaScript uses: the exports it
 * imports, the store it reads and writes, and the hooks it adds callbacks to. A
 * failure here means a change would break MailPoet's email editor.
 *
 * The list comes from MailPoet's `assets/js/src`. Update it when MailPoet starts
 * or stops using a part of the package. The source is read with the TypeScript
 * parser because importing the package entry point loads the whole block editor.
 */

const SRC = join( __dirname, '..' );

function parse( file: string ): ts.SourceFile {
	return ts.createSourceFile(
		file,
		readFileSync( file, 'utf8' ),
		ts.ScriptTarget.Latest,
		true
	);
}

/**
 * Names a module exports directly (not through `export *`).
 *
 * @param file Module file.
 */
function exportedNames( file: string ): string[] {
	const names: string[] = [];
	parse( file ).forEachChild( ( node ) => {
		if ( ts.isExportDeclaration( node ) && node.exportClause ) {
			if ( ts.isNamedExports( node.exportClause ) ) {
				node.exportClause.elements.forEach( ( element ) =>
					names.push( element.name.text )
				);
			}
		}
		const modifiers = ts.canHaveModifiers( node )
			? ts.getModifiers( node )
			: undefined;
		const isExported = modifiers?.some(
			( modifier ) => modifier.kind === ts.SyntaxKind.ExportKeyword
		);
		if ( ! isExported ) {
			return;
		}
		if ( ts.isVariableStatement( node ) ) {
			node.declarationList.declarations.forEach( ( declaration ) => {
				if ( ts.isIdentifier( declaration.name ) ) {
					names.push( declaration.name.text );
				}
			} );
		} else if (
			( ts.isFunctionDeclaration( node ) ||
				ts.isTypeAliasDeclaration( node ) ||
				ts.isInterfaceDeclaration( node ) ) &&
			node.name
		) {
			names.push( node.name.text );
		}
	} );
	return names;
}

function sourceFiles( dir: string ): string[] {
	return readdirSync( dir ).flatMap( ( name ) => {
		const path = join( dir, name );
		if ( statSync( path ).isDirectory() ) {
			return name === 'test' || name === '__mocks__'
				? []
				: sourceFiles( path );
		}
		return /\.(ts|tsx|js)$/.test( name ) && ! /\.d\.ts$/.test( name )
			? [ path ]
			: [];
	} );
}

/**
 * Most arguments passed to each hook fired with a string literal name.
 */
function firedHooks(): Map< string, number > {
	const hooks = new Map< string, number >();
	sourceFiles( SRC ).forEach( ( file ) => {
		const visit = ( node: ts.Node ) => {
			if (
				ts.isCallExpression( node ) &&
				ts.isIdentifier( node.expression ) &&
				[ 'applyFilters', 'doAction' ].includes(
					node.expression.text
				) &&
				node.arguments.length > 0 &&
				ts.isStringLiteral( node.arguments[ 0 ] )
			) {
				const name = node.arguments[ 0 ].text;
				const args = node.arguments.length - 1;
				hooks.set( name, Math.max( hooks.get( name ) ?? 0, args ) );
			}
			ts.forEachChild( node, visit );
		};
		visit( parse( file ) );
	} );
	return hooks;
}

describe( 'MailPoet contract', () => {
	it( 'keeps the store name MailPoet selects and dispatches to', () => {
		expect( storeName ).toBe( 'email-editor/editor' );
	} );

	it.each( [
		[ 'index.ts', 'initializeEditor' ],
		[ 'index.ts', 'storeName' ],
		[ 'index.ts', 'EmailActionsFill' ],
		[ 'index.ts', 'TemplateSelection' ],
		[ 'index.ts', 'RecentEmailsQuery' ],
		[ 'store/constants.ts', 'storeName' ],
		[ 'store/types.ts', 'EmailContentValidationRule' ],
		[ 'store/types.ts', 'RecentEmailsQuery' ],
		[ 'store/selectors.ts', 'getEmailPostType' ],
		[ 'store/selectors.ts', 'isEmailSent' ],
		[ 'store/actions.ts', 'setEmailPost' ],
		[ 'store/actions.ts', 'togglePreviewModal' ],
	] )( '%s exports %s', ( file, name ) => {
		expect( exportedNames( join( SRC, file ) ) ).toContain( name );
	} );

	it( 'keeps the store re-exporting its constants and types', () => {
		const source = readFileSync( join( SRC, 'store/index.ts' ), 'utf8' );

		expect( source ).toContain( "export * from './constants';" );
		expect( source ).toContain( "export * from './types';" );
	} );

	it.each( [
		[ 'woocommerce_email_editor_wrap_editor_component', 1 ],
		[ 'woocommerce_email_editor_content_validation_rules', 1 ],
		[ 'woocommerce_email_editor_send_button_label', 1 ],
		[ 'woocommerce_email_editor_send_action_callback', 1 ],
		[ 'woocommerce_email_editor_send_button_disabled', 2 ],
		[ 'woocommerce_email_editor_events', 1 ],
		[ 'woocommerce_email_editor_events_tracking_enabled', 1 ],
		[ 'woocommerce_email_editor_preferred_template_title', 2 ],
		[ 'woocommerce_email_editor_recent_emails_query', 2 ],
	] )( 'fires %s with at least %d argument(s)', ( hook, args ) => {
		const fired = firedHooks();

		expect( fired.has( hook ) ).toBe( true );
		expect( fired.get( hook ) ).toBeGreaterThanOrEqual( args );
	} );

	it( 'finds hooks in the source at all', () => {
		// Guards the helpers above: an empty map would make every hook check fail
		// for the wrong reason, so this test names that cause directly.
		expect(
			[ ...firedHooks().keys() ].filter( ( name ) =>
				name.startsWith( 'woocommerce_email_editor_' )
			).length
		).toBeGreaterThan( 9 );
	} );
} );
