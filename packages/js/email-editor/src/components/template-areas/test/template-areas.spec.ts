/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import {
	getTemplateAreaClientIds,
	isTemplateAreaEditingEnabled,
} from '../template-areas';
import { storeName } from '../../../store';

jest.mock( '@wordpress/block-editor', () => ( {
	store: 'core/block-editor',
} ) );
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ) );
jest.mock( '../../../store', () => ( { storeName: 'email-editor' } ) );

type Tree = Record< string, string[] >;

const createSelect =
	( tree: Tree, editor = {}, emailEditor = {} ) =>
	( store: unknown ) => {
		if ( store === blockEditorStore ) {
			const parentOf = ( id: string ) =>
				Object.keys( tree ).find( ( key ) =>
					tree[ key ].includes( id )
				);
			return {
				getBlocksByName: ( name: string ) =>
					name === 'core/post-content' &&
					Object.values( tree ).flat().includes( 'content' )
						? [ 'content' ]
						: [],
				getBlockOrder: ( id: string ) => tree[ id ] ?? [],
				// Blocks in the fixtures are groups unless their ID says otherwise.
				getBlockName: ( id: string ) =>
					[
						'logo',
						'powered-by',
						'site-title',
						'paragraph',
					].includes( id )
						? `core/${ id }`
						: 'core/group',
				getBlockParents: ( id: string ) => {
					const parents: string[] = [];
					let parent = parentOf( id );
					while ( parent ) {
						parents.unshift( parent );
						parent = parentOf( parent );
					}
					return parents;
				},
			};
		}
		if ( store === editorStore ) {
			return {
				getRenderingMode: () => 'template-locked',
				getCurrentPostType: () => 'mailpoet_email',
				getEditorSettings: () => ( {
					onNavigateToEntityRecord: jest.fn(),
				} ),
				...editor,
			};
		}
		if ( store === storeName ) {
			return {
				canUserEditTemplates: () => true,
				getCurrentTemplate: () => ( { id: 'theme//newsletter' } ),
				...emailEditor,
			};
		}
		return {};
	};

describe( 'getTemplateAreaClientIds', () => {
	it( 'returns the template groups around the content, at every level', () => {
		// The MailPoet newsletter template: a wrapper group with the header,
		// the content, the footer and the "Powered by" block.
		const select = createSelect( {
			'': [ 'wrapper' ],
			wrapper: [ 'header', 'content', 'footer', 'powered-by' ],
			header: [ 'logo' ],
		} );

		expect( getTemplateAreaClientIds( select as never ) ).toEqual( [
			'header',
			'footer',
		] );
	} );

	it( 'keeps template blocks that are not groups locked, so their content cannot be edited from the email', () => {
		// The WooCommerce template puts the site title next to the content.
		const select = createSelect( {
			'': [ 'wrapper' ],
			wrapper: [ 'site-title', 'content-group', 'footer' ],
			'content-group': [ 'content' ],
		} );

		expect( getTemplateAreaClientIds( select as never ) ).toEqual( [
			'footer',
		] );
	} );

	it( 'skips the groups that only wrap the content', () => {
		// The WooCommerce template wraps the content in an extra group.
		const select = createSelect( {
			'': [ 'wrapper' ],
			wrapper: [ 'header', 'content-group', 'footer' ],
			'content-group': [ 'content' ],
		} );

		expect( getTemplateAreaClientIds( select as never ) ).toEqual( [
			'header',
			'footer',
		] );
	} );

	it( 'returns nothing when the canvas shows no email content', () => {
		const select = createSelect( { '': [ 'paragraph' ] } );

		expect( getTemplateAreaClientIds( select as never ) ).toEqual( [] );
	} );
} );

describe( 'isTemplateAreaEditingEnabled', () => {
	it( 'is on when the email is shown inside an editable template', () => {
		expect(
			isTemplateAreaEditingEnabled( createSelect( {} ) as never )
		).toBe( true );
	} );

	it.each( [
		[ 'the template is hidden', { getRenderingMode: () => 'post-only' } ],
		[
			'the template itself is open',
			{ getCurrentPostType: () => 'wp_template' },
		],
		[
			'the editor cannot open the template',
			{ getEditorSettings: () => ( {} ) },
		],
	] )( 'is off when %s', ( _, editor ) => {
		expect(
			isTemplateAreaEditingEnabled( createSelect( {}, editor ) as never )
		).toBe( false );
	} );

	it( 'is off for users who cannot edit templates', () => {
		const select = createSelect(
			{},
			{},
			{ canUserEditTemplates: () => false }
		);

		expect( isTemplateAreaEditingEnabled( select as never ) ).toBe( false );
	} );
} );
