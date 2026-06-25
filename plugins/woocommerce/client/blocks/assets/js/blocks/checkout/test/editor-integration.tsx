/**
 * External dependencies
 */
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';

jest.mock( '@wordpress/data', () =>
	// eslint-disable-next-line @typescript-eslint/no-var-requires -- Must use require due to Jest mock hoisting
	require( '@woocommerce/blocks-test-utils/mock-editor-store' ).mockWordPressDataWithEditorStore()
);

/**
 * Internal dependencies
 */
import { getAllowedBlocks } from '../../cart-checkout-shared/editor-utils';

describe( 'Checkout block editor integration', () => {
	beforeAll( async () => {
		// Register a checkout filter to allow `core/table` block in all Checkout inner blocks,
		// add `core/audio` into the woocommerce/checkout-totals-block specifically
		registerCheckoutFilters( 'woo-test-namespace', {
			// @ts-expect-error - The types for the checkout filters are not defined.
			additionalCartCheckoutInnerBlockTypes: (
				value: string[],
				extensions,
				{ block }: { block: string }
			) => {
				value.push( 'core/table' );
				if ( block === 'woocommerce/checkout-totals-block' ) {
					value.push( 'core/audio' );
				}
				return value;
			},
		} );
	} );

	it( 'inner blocks can be added/removed by filters', () => {
		expect(
			getAllowedBlocks( 'woocommerce/checkout-totals-block' )
		).toEqual( expect.arrayContaining( [ 'core/table', 'core/audio' ] ) );

		expect(
			getAllowedBlocks( 'woocommerce/checkout-contact-information-block' )
		).toEqual( expect.arrayContaining( [ 'core/table' ] ) );
		expect(
			getAllowedBlocks( 'woocommerce/checkout-contact-information-block' )
		).not.toContain( 'core/audio' );
	} );
} );
