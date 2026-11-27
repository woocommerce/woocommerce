/**
 * External dependencies
 */
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { applyFilters } from '@wordpress/hooks';
import { serialize } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	canUserEditTemplates,
	getCurrentTemplate,
	getEditedEmailContent,
	getEditedPostTemplate,
	getEmailTemplates,
	getSentEmailEditorPosts,
	hasEmptyContent,
	isEmailSent,
} from '../selectors';
import { storeName } from '../constants';

// Importing the selectors module pulls in the whole block editor, which Jest
// cannot transform. Only the store descriptors are needed to route `select`.
jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );
jest.mock( '@wordpress/editor', () => ( { store: { name: 'core/editor' } } ) );
jest.mock( '@wordpress/preferences', () => ( {
	store: { name: 'core/preferences' },
} ) );
jest.mock( '@wordpress/blocks', () => ( {
	serialize: jest.fn( () => '<!-- serialized -->' ),
	parse: jest.fn(),
} ) );
jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _hook, value ) => value ),
} ) );

type Selectors = Record< string, jest.Mock | ( ( ...args ) => unknown ) >;
type Registry = { select: jest.Mock };

const registrySelectors = [
	canUserEditTemplates,
	getCurrentTemplate,
	getEditedEmailContent,
	getEditedPostTemplate,
	getEmailTemplates,
	getSentEmailEditorPosts,
	hasEmptyContent,
	isEmailSent,
] as unknown as { registry?: Registry }[];

/**
 * Gives every selector under test a fresh registry that answers from the given
 * store selectors. A store the test didn't set up throws, so a selector that
 * reads something unexpected fails loudly.
 *
 * @param stores        Selectors per store.
 * @param stores.email  Email editor store selectors.
 * @param stores.core   Core data store selectors.
 * @param stores.editor Editor store selectors.
 */
function buildRegistry( stores: {
	email?: Selectors;
	core?: Selectors;
	editor?: Selectors;
} ) {
	const registry: Registry = {
		select: jest.fn( ( store ) => {
			if ( store === storeName ) {
				return {
					getEmailPostId: () => 12,
					getEmailPostType: () => 'woo_email',
					...stores.email,
				};
			}
			if ( store === coreDataStore && stores.core ) {
				return stores.core;
			}
			if ( store === editorStore && stores.editor ) {
				return stores.editor;
			}
			throw new Error( `Unexpected store: ${ String( store ) }` );
		} ),
	};
	registrySelectors.forEach( ( selector ) => {
		selector.registry = registry;
	} );
}

describe( 'email editor store selectors', () => {
	afterEach( () => {
		registrySelectors.forEach( ( selector ) => {
			delete selector.registry;
		} );
		jest.clearAllMocks();
	} );

	describe( 'hasEmptyContent', () => {
		it.each( [
			[ 'there is no post yet', undefined, true ],
			[ 'the post has no content', { content: { raw: '' } }, true ],
			[
				'the post has content',
				{ content: { raw: '<p>Hi</p>' } },
				false,
			],
			[ 'the record has no content field', {}, true ],
		] )( 'is %s → %s', ( _case, post, expected ) => {
			buildRegistry( { core: { getEntityRecord: () => post } } );

			expect( hasEmptyContent() ).toBe( expected );
		} );
	} );

	describe( 'isEmailSent', () => {
		it.each( [
			[ undefined, false ],
			[ { status: 'sent' }, true ],
			[ { status: 'draft' }, false ],
		] )( 'for post %j returns %s', ( post, expected ) => {
			buildRegistry( { core: { getEntityRecord: () => post } } );

			expect( isEmailSent() ).toBe( expected );
		} );
	} );

	describe( 'getEditedEmailContent', () => {
		it( 'prefers the content callback, then blocks, then the content string', () => {
			const record = ( value: unknown ) =>
				buildRegistry( {
					core: { getEditedEntityRecord: () => value },
				} );

			record( { content: () => 'from callback', blocks: [ {} ] } );
			expect( getEditedEmailContent() ).toBe( 'from callback' );

			record( { blocks: [ {} ], content: 'stale string' } );
			expect( getEditedEmailContent() ).toBe( '<!-- serialized -->' );
			expect( serialize ).toHaveBeenCalledWith( [ {} ] );

			record( { content: '<p>Plain</p>' } );
			expect( getEditedEmailContent() ).toBe( '<p>Plain</p>' );

			record( undefined );
			expect( getEditedEmailContent() ).toBe( '' );
		} );
	} );

	describe( 'getSentEmailEditorPosts', () => {
		it( 'lists published and sent emails with content, using the filtered query', () => {
			const getEntityRecords = jest.fn( () => [
				{ id: 1, content: { raw: '<p>One</p>' } },
				{ id: 2, content: { raw: '' } },
			] );
			buildRegistry( { core: { getEntityRecords } } );
			( applyFilters as jest.Mock ).mockImplementationOnce(
				( _hook, query ) => ( {
					...query,
					status: 'publish,sent,draft',
				} )
			);

			const posts = getSentEmailEditorPosts();

			expect( posts.map( ( post ) => post.id ) ).toEqual( [ 1 ] );
			expect( applyFilters ).toHaveBeenCalledWith(
				'woocommerce_email_editor_recent_emails_query',
				{ per_page: 30, status: 'publish,sent' },
				'woo_email'
			);
			expect( getEntityRecords ).toHaveBeenCalledWith(
				'postType',
				'woo_email',
				{ per_page: 30, status: 'publish,sent,draft' }
			);
		} );

		it.each( [ [ null ], [ 'publish' ], [ [ 'publish' ] ] ] )(
			'falls back to the default query when a filter returns %j',
			( filtered ) => {
				const getEntityRecords = jest.fn( () => null );
				buildRegistry( { core: { getEntityRecords } } );
				( applyFilters as jest.Mock ).mockReturnValueOnce( filtered );

				expect( getSentEmailEditorPosts() ).toEqual( [] );
				expect( getEntityRecords ).toHaveBeenCalledWith(
					'postType',
					'woo_email',
					{ per_page: 30, status: 'publish,sent' }
				);
			}
		);
	} );

	describe( 'getEditedPostTemplate', () => {
		const templates = [
			{ id: 'theme//email-general', slug: 'email-general' },
			{ id: 'theme//promo', slug: 'promo' },
		];

		function buildTemplateRegistry( canEdit: boolean ) {
			const getEditedEntityRecord = jest.fn( ( _kind, _name, id ) => ( {
				id,
				edited: true,
			} ) );
			const getEntityRecord = jest.fn( ( _kind, _name, id ) => ( {
				id,
				title: { raw: 'Raw title' },
				content: { raw: 'Raw content' },
			} ) );
			buildRegistry( {
				core: {
					getEntityRecords: () => templates,
					getDefaultTemplateId: () => 'theme//email-general',
					canUser: () => canEdit,
					getEditedEntityRecord,
					getEntityRecord,
				},
				editor: { getEditedPostAttribute: () => '' },
			} );
		}

		it( 'returns the edited template for the given slug', () => {
			buildTemplateRegistry( true );

			expect( getEditedPostTemplate( undefined, 'promo' ) ).toEqual( {
				id: 'theme//promo',
				edited: true,
			} );
		} );

		it( 'returns null when no template has the slug', () => {
			buildTemplateRegistry( true );

			expect( getEditedPostTemplate( undefined, 'missing' ) ).toBeNull();
		} );

		it( 'uses the default email-general template when the post has none', () => {
			buildTemplateRegistry( true );

			expect( getEditedPostTemplate( undefined ) ).toEqual( {
				id: 'theme//email-general',
				edited: true,
			} );
		} );

		it( 'returns plain title and content to users who cannot edit templates', () => {
			buildTemplateRegistry( false );

			expect( getEditedPostTemplate( undefined, 'promo' ) ).toEqual(
				expect.objectContaining( {
					id: 'theme//promo',
					title: 'Raw title',
					content: 'Raw content',
				} )
			);
		} );
	} );

	describe( 'getCurrentTemplate', () => {
		it( 'returns the template being edited in template mode', () => {
			buildRegistry( {
				editor: {
					getCurrentPostType: () => 'wp_template',
					getCurrentPostId: () => 'theme//promo',
				},
				core: {
					getEditedEntityRecord: ( _kind, _name, id ) => ( { id } ),
				},
			} );

			expect( getCurrentTemplate() ).toEqual( { id: 'theme//promo' } );
		} );

		it( "returns the email's template while editing an email", () => {
			buildRegistry( {
				editor: { getCurrentPostType: () => 'woo_email' },
				email: {
					getEditedPostTemplate: () => ( { id: 'email-template' } ),
				},
			} );

			expect( getCurrentTemplate() ).toEqual( { id: 'email-template' } );
		} );
	} );

	describe( 'getEmailTemplates', () => {
		it( 'keeps only templates for the email post type', () => {
			buildRegistry( {
				core: {
					getEntityRecords: () => [
						{ id: 'a', post_types: [ 'woo_email' ] },
						{ id: 'b', post_types: [ 'page' ] },
						{ id: 'c' },
					],
				},
			} );

			expect(
				getEmailTemplates().map( ( template ) => template.id )
			).toEqual( [ 'a' ] );
		} );
	} );
} );
