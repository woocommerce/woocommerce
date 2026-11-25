/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
	createBlock: ( name, attributes, innerBlocks = [] ) => ( {
		name,
		attributes,
		innerBlocks,
	} ),
} ) );
jest.mock( '../edit', () => ( { Edit: () => null, Save: () => null } ) );
jest.mock( '../inner-blocks', () => ( {} ) );

/**
 * Internal dependencies
 */
import '../index';

const migrateV1 = ( attributes ) => {
	const [ , settings ] = registerBlockType.mock.calls[ 0 ];
	const [ , innerBlocks ] = settings.deprecated[ 0 ].migrate( {
		showOrderNotes: true,
		showReturnToCart: false,
		cartPageId: 0,
		...attributes,
	} );
	return innerBlocks[ 0 ].innerBlocks;
};

describe( 'Checkout v1 migration', () => {
	it.each( [ true, false ] )(
		'preserves the separate Terms block when showPolicyLinks is %s',
		( showPolicyLinks ) => {
			const fields = migrateV1( { showPolicyLinks } );

			expect(
				fields.some(
					( block ) =>
						block.name === 'woocommerce/checkout-terms-block'
				)
			).toBe( showPolicyLinks );
			expect(
				fields.find(
					( block ) =>
						block.name === 'woocommerce/checkout-actions-block'
				).attributes.showTerms
			).toBeUndefined();
		}
	);
} );
