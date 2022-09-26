/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect } from '@wordpress/data';
import { Form } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductDetailsSection } from '../product-details-section';
import { validate } from '../../product-validation';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

describe( 'ProductDetailsSection', () => {
	const useSelectMock = useSelect as jest.Mock;

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'when editing a product', () => {
		const product: Partial< Product > = {
			id: 1,
			name: 'Lorem',
			slug: 'lorem',
		};
		const permalinkPrefix = 'http://localhost/';
		const linkUrl = permalinkPrefix + product.slug;

		beforeEach( () => {
			useSelectMock.mockReturnValue( {
				permalinkPrefix,
			} );
		} );

		it( 'should render the product link', () => {
			render(
				<Form initialValues={ product } validate={ validate }>
					<ProductDetailsSection />
				</Form>
			);

			expect( screen.queryByText( linkUrl ) ).toBeInTheDocument();
		} );

		it( 'should hide the product link if field name has errors', () => {
			render(
				<Form initialValues={ product } validate={ validate }>
					<ProductDetailsSection />
				</Form>
			);
			userEvent.clear( screen.getByLabelText( 'Name' ) );
			userEvent.tab();

			expect( screen.queryByText( linkUrl ) ).not.toBeInTheDocument();
		} );
	} );
} );
