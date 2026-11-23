/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useStoreCart } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '..';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../mocks/shipping-package';

jest.mock( '@woocommerce/base-context', () => ( {
	useStoreCart: jest.fn(),
	useEditorContext: jest.fn( () => ( { isEditor: false } ) ),
	useShippingData: jest.fn( () => ( {
		hasSelectedLocalPickup: false,
		selectedRates: {},
	} ) ),
} ) );

const skeletonRowSelector = '.wc-block-components-skeleton__shipping-option';

const renderLoading = (
	shippingRates: ReturnType< typeof generateShippingPackage >[]
) =>
	render(
		<ShippingRatesControl
			shippingRates={ shippingRates }
			isLoadingRates={ true }
			context="woocommerce/checkout"
		/>
	);

describe( 'ShippingRatesControl loading state', () => {
	beforeEach( () => {
		jest.mocked( useStoreCart ).mockReturnValue( {
			extensions: {},
			receiveCart: jest.fn(),
		} as unknown as ReturnType< typeof useStoreCart > );
	} );

	it( 'shows a skeleton row for each rate already on screen', () => {
		const { container } = renderLoading( [
			generateShippingPackage( {
				packageId: 0,
				shippingRates: [ 1, 2, 3 ].map( ( instanceID ) =>
					generateShippingRate( {
						rateId: `flat_rate:${ instanceID }`,
						name: `Rate ${ instanceID }`,
						price: '1000',
						instanceID,
					} )
				),
			} ),
		] );

		expect(
			container.querySelectorAll( skeletonRowSelector )
		).toHaveLength( 3 );
	} );

	it( 'shows a single skeleton row when there are no rates on screen', () => {
		const { container } = renderLoading( [] );

		expect(
			container.querySelectorAll( skeletonRowSelector )
		).toHaveLength( 1 );
	} );
} );
