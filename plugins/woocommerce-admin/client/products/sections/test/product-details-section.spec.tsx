/**
 * External dependencies
 */
import { createRegistry, RegistryProvider, useSelect } from '@wordpress/data';
import { Form } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { render, screen } from '@testing-library/react';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as blockEditorStore } from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreDataStore } from '@wordpress/core-data';
// eslint-disable-next-line @woocommerce/dependency-group
import userEvent from '@testing-library/user-event';

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

const registry = createRegistry();
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
registry.register( coreDataStore );
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
registry.register( blockEditorStore );

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
				<RegistryProvider value={ registry }>
					<Form initialValues={ product } validate={ validate }>
						<ProductDetailsSection />
					</Form>
				</RegistryProvider>
			);

			expect( screen.queryByText( linkUrl ) ).toBeInTheDocument();
		} );

		it( 'should hide the product link if field name has errors', () => {
			render(
				<RegistryProvider value={ registry }>
					<Form initialValues={ product } validate={ validate }>
						<ProductDetailsSection />
					</Form>
				</RegistryProvider>
			);
			userEvent.clear(
				screen.getByLabelText( 'Name', { exact: false } )
			);
			userEvent.tab();

			expect( screen.queryByText( linkUrl ) ).not.toBeInTheDocument();
		} );
	} );
} );
