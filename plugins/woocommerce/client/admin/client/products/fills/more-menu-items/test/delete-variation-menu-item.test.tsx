/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { DeleteVariationMenuItem } from '../delete-variation-menu-item';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( 'react-router-dom', () => ( { useParams: jest.fn() } ) );

jest.mock( '@wordpress/core-data', () => ( {
	useEntityId: jest.fn().mockReturnValue( 'variation_1' ),
	useEntityProp: jest
		.fn()
		.mockImplementation( ( _1, _2, propType ) => [ propType ] ),
} ) );

describe( 'DeleteVariationMenuItem', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_dropdown_option_click track event when clicking the menu', async () => {
		( useDispatch as jest.Mock ).mockReturnValue( {
			deleteProductVariation: () => {},
		} );
		( useSelect as jest.Mock ).mockReturnValue( {
			type: 'simple',
			status: 'publish',
		} );
		( useParams as jest.Mock ).mockReturnValue( { productId: 1 } );
		const { getByText } = render(
			<DeleteVariationMenuItem onClose={ () => {} } />
		);
		fireEvent.click( getByText( 'Delete variation' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'product_dropdown_option_click',
			{
				product_id: 1,
				product_status: 'status',
				selected_option: 'delete_variation',
				variation_id: 'variation_1',
			}
		);
	} );
} );
