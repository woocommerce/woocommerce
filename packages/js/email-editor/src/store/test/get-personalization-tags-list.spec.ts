/**
 * External dependencies
 */
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { getPersonalizationTagsList } from '../selectors';
import { storeName } from '../constants';
import { PersonalizationTag } from '../types';

// Importing the selectors module pulls in the whole block editor, which Jest
// cannot transform. Only the store descriptors are needed to route `select`.
jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );
jest.mock( '@wordpress/editor', () => ( { store: { name: 'core/editor' } } ) );
jest.mock( '@wordpress/preferences', () => ( {
	store: { name: 'core/preferences' },
} ) );
jest.mock( '@wordpress/blocks', () => ( {
	serialize: jest.fn(),
	parse: jest.fn(),
} ) );

const makeTag = ( name: string, postTypes: string[] ): PersonalizationTag => ( {
	name,
	token: `[woocommerce/${ name }]`,
	category: 'Test',
	attributes: [],
	valueToInsert: `[woocommerce/${ name }]`,
	postTypes,
} );

type Registry = { select: jest.Mock };

/**
 * `createRegistrySelector` resolves the selector once per registry, so a fresh
 * registry per test is what gives each test empty caches.
 *
 * @param options          Store values the selector reads.
 * @param options.tags     Records returned by `getEntityRecords`.
 * @param options.postType Post type currently being edited.
 * @param options.template Result of `getCurrentTemplate()`.
 */
function buildRegistry( options: {
	tags: PersonalizationTag[] | null;
	postType: string | undefined;
	template?: { post_types: string[] } | null;
} ) {
	const { tags, postType, template = null } = options;
	const getEntityRecords = jest.fn().mockReturnValue( tags );

	const registry: Registry = {
		select: jest.fn( ( store ) => {
			if ( store === storeName ) {
				return {
					getEmailPostId: () => 23,
					getEmailPostType: () => postType,
					getCurrentTemplate: () => template,
				};
			}
			if ( store === coreDataStore ) {
				return { getEntityRecords };
			}
			throw new Error( `Unexpected store: ${ String( store ) }` );
		} ),
	};

	(
		getPersonalizationTagsList as unknown as { registry: Registry }
	 ).registry = registry;

	return { getEntityRecords };
}

const names = () => getPersonalizationTagsList().map( ( tag ) => tag.name );

describe( 'getPersonalizationTagsList', () => {
	// Without this, a test that forgot `buildRegistry` would silently reuse the
	// previous registry along with its warm caches.
	afterEach( () => {
		delete (
			getPersonalizationTagsList as unknown as { registry?: Registry }
		 ).registry;
	} );

	it( 'filters tags by the edited post type', () => {
		buildRegistry( {
			tags: [
				makeTag( 'a', [ 'woo_email' ] ),
				makeTag( 'b', [ 'other_type' ] ),
				makeTag( 'c', [] ),
			],
			postType: 'woo_email',
		} );

		expect( names() ).toEqual( [ 'a', 'c' ] );
	} );

	it( 'filters tags by the template post types when editing a template', () => {
		buildRegistry( {
			tags: [
				makeTag( 'a', [ 'woo_email' ] ),
				makeTag( 'b', [ 'other_type' ] ),
				makeTag( 'c', [] ),
			],
			postType: 'wp_template',
			template: { post_types: [ 'other_type' ] },
		} );

		expect( names() ).toEqual( [ 'b', 'c' ] );
	} );

	// `getEditedPostTemplate` returns null while a template is unresolved, which
	// the previous unguarded `postTemplate.post_types` threw a TypeError on.
	it( 'keeps only untyped tags when the template is unresolved', () => {
		buildRegistry( {
			tags: [ makeTag( 'a', [ 'woo_email' ] ), makeTag( 'c', [] ) ],
			postType: 'wp_template',
			template: null,
		} );

		expect( names() ).toEqual( [ 'c' ] );
	} );

	it( 'returns a referentially stable list across repeated calls', () => {
		buildRegistry( {
			tags: [ makeTag( 'a', [ 'woo_email' ] ) ],
			postType: 'woo_email',
		} );

		expect( getPersonalizationTagsList() ).toBe(
			getPersonalizationTagsList()
		);
	} );

	// The counterpart to the test above: stable is only correct while the
	// records are unchanged.
	it( 'recomputes when the records returned by core-data change', () => {
		const { getEntityRecords } = buildRegistry( {
			tags: [ makeTag( 'a', [ 'woo_email' ] ) ],
			postType: 'woo_email',
		} );
		const before = getPersonalizationTagsList();

		getEntityRecords.mockReturnValue( [
			makeTag( 'a', [ 'woo_email' ] ),
			makeTag( 'b', [ 'woo_email' ] ),
		] );

		expect( getPersonalizationTagsList() ).not.toBe( before );
		expect( names() ).toEqual( [ 'a', 'b' ] );
	} );

	// Records are null until the request resolves, which is every call during
	// initial load — a fresh array each time would defeat the memoization.
	it( 'returns a stable empty list while the records are unresolved', () => {
		buildRegistry( { tags: null, postType: 'woo_email' } );

		expect( getPersonalizationTagsList() ).toStrictEqual( [] );
		expect( getPersonalizationTagsList() ).toBe(
			getPersonalizationTagsList()
		);
	} );
} );
