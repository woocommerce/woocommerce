/**
 * External dependencies
 */
import type { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import deprecated from '../deprecated';

function createTestBlock( block: {
	clientId: string;
	name: string;
	attributes?: Record< string, unknown >;
	innerBlocks?: BlockInstance[];
	originalContent?: string;
} ): BlockInstance {
	return {
		isValid: true,
		attributes: {},
		innerBlocks: [],
		...block,
	};
}

jest.mock( '@wordpress/blocks', () => {
	const actual = jest.requireActual( '@wordpress/blocks' );

	return {
		...actual,
		createBlock: jest.fn(
			(
				name: string,
				attributes = {},
				innerBlocks: BlockInstance[] = []
			) => ( {
				clientId: `mock-${ name }`,
				name,
				attributes,
				innerBlocks,
				isValid: true,
			} )
		),
	};
} );

const LEGACY_ATTRIBUTE_OPTIONS_BLOCK =
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options';
const INNER_CHIPS = 'woocommerce/product-filter-chips';
const INNER_DROPDOWN = 'woocommerce/dropdown';

const { isEligible, migrate } = deprecated[ 0 ];

type MigrateResult = [ Record< string, unknown >, BlockInstance[] ];

function runMigrate(
	attributes: Record< string, unknown >,
	innerBlocks: BlockInstance[]
): MigrateResult {
	return migrate( attributes, innerBlocks ) as MigrateResult;
}

function createLegacyMissingBlock( originalContent = '' ): BlockInstance {
	return createTestBlock( {
		clientId: 'legacy-options',
		name: 'core/missing',
		attributes: {
			originalName: LEGACY_ATTRIBUTE_OPTIONS_BLOCK,
		},
		originalContent,
	} );
}

function createAttributeNameBlock(): BlockInstance {
	return createTestBlock( {
		clientId: 'attribute-name',
		name: 'woocommerce/add-to-cart-with-options-variation-selector-attribute-name',
	} );
}

function createGroupWithInnerBlocks(
	innerBlocks: BlockInstance[]
): BlockInstance {
	return createTestBlock( {
		clientId: 'group',
		name: 'core/group',
		innerBlocks,
	} );
}

describe( 'Variation Selector Attribute block deprecation', () => {
	describe( 'isEligible', () => {
		it( 'returns false when there is no legacy attribute options block', () => {
			const innerBlocks = [ createAttributeNameBlock() ];

			expect( isEligible( {}, innerBlocks ) ).toBe( false );
		} );

		it( 'returns true when a legacy attribute options block is a direct inner block', () => {
			const innerBlocks = [
				createAttributeNameBlock(),
				createLegacyMissingBlock(),
			];

			expect( isEligible( {}, innerBlocks ) ).toBe( true );
		} );

		it( 'returns true when a legacy attribute options block is nested in inner blocks', () => {
			const innerBlocks = [
				createGroupWithInnerBlocks( [
					createAttributeNameBlock(),
					createLegacyMissingBlock(),
				] ),
			];

			expect( isEligible( {}, innerBlocks ) ).toBe( true );
		} );
	} );

	describe( 'migrate', () => {
		it( 'replaces a legacy dropdown options block and sets displayStyle to dropdown', () => {
			const attributes = {
				displayStyle: INNER_CHIPS,
				autoselect: true,
				disabledAttributesAction: 'hide' as const,
				customAttribute: 'preserved',
			};
			const innerBlocks = [
				createAttributeNameBlock(),
				createLegacyMissingBlock(
					'<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-options {"optionStyle":"dropdown"} /-->'
				),
			];

			const [ migratedAttributes, migratedInnerBlocks ] = runMigrate(
				attributes,
				innerBlocks
			);

			expect( migratedAttributes ).toEqual( {
				...attributes,
				displayStyle: INNER_DROPDOWN,
				autoselect: metadata.attributes.autoselect.default,
				disabledAttributesAction:
					metadata.attributes.disabledAttributesAction.default,
			} );
			expect( migratedInnerBlocks ).toHaveLength( 2 );
			expect( migratedInnerBlocks[ 0 ].name ).toBe(
				'woocommerce/add-to-cart-with-options-variation-selector-attribute-name'
			);
			expect( migratedInnerBlocks[ 1 ].name ).toBe( INNER_DROPDOWN );
		} );

		it( 'replaces a legacy chips options block and sets displayStyle to chips', () => {
			const attributes = {
				displayStyle: INNER_DROPDOWN,
			};
			const innerBlocks = [
				createLegacyMissingBlock(
					'<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-options {"optionStyle":"pills"} /-->'
				),
			];

			const [ migratedAttributes, migratedInnerBlocks ] = runMigrate(
				attributes,
				innerBlocks
			);

			expect( migratedAttributes.displayStyle ).toBe( INNER_CHIPS );
			expect( migratedInnerBlocks ).toHaveLength( 1 );
			expect( migratedInnerBlocks[ 0 ].name ).toBe( INNER_CHIPS );
		} );

		it( 'defaults to chips when legacy block content does not indicate dropdown', () => {
			const innerBlocks = [ createLegacyMissingBlock() ];

			const [ migratedAttributes, migratedInnerBlocks ] = runMigrate(
				{},
				innerBlocks
			);

			expect( migratedAttributes.displayStyle ).toBe( INNER_CHIPS );
			expect( migratedInnerBlocks[ 0 ].name ).toBe( INNER_CHIPS );
		} );

		it( 'uses block.json defaults for autoselect and disabledAttributesAction', () => {
			const innerBlocks = [ createLegacyMissingBlock() ];

			const [ migratedAttributes ] = runMigrate( {}, innerBlocks );

			expect( migratedAttributes.autoselect ).toBe(
				metadata.attributes.autoselect.default
			);
			expect( migratedAttributes.disabledAttributesAction ).toBe(
				metadata.attributes.disabledAttributesAction.default
			);
		} );

		it( 'replaces a nested legacy attribute options block', () => {
			const innerBlocks = [
				createGroupWithInnerBlocks( [
					createAttributeNameBlock(),
					createLegacyMissingBlock(
						'<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-options {"optionStyle":"dropdown"} /-->'
					),
				] ),
			];

			const [ migratedAttributes, migratedInnerBlocks ] = runMigrate(
				{},
				innerBlocks
			);

			expect( migratedAttributes.displayStyle ).toBe( INNER_DROPDOWN );
			expect( migratedInnerBlocks ).toHaveLength( 1 );
			expect( migratedInnerBlocks[ 0 ].name ).toBe( 'core/group' );
			expect( migratedInnerBlocks[ 0 ].innerBlocks ).toHaveLength( 2 );
			expect( migratedInnerBlocks[ 0 ].innerBlocks[ 0 ].name ).toBe(
				'woocommerce/add-to-cart-with-options-variation-selector-attribute-name'
			);
			expect( migratedInnerBlocks[ 0 ].innerBlocks[ 1 ].name ).toBe(
				INNER_DROPDOWN
			);
		} );
	} );
} );
