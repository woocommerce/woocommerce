/**
 * Internal dependencies
 */

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { default as fetchMock } from 'jest-fetch-mock';

/**
 * Internal dependencies
 */
import Block from '../block';
import { allSettings } from '../../../settings/shared/settings-init';

const mockResults = {
	stock_status_counts: [
		{ status: 'instock', count: '18' },
		{ status: 'outofstock', count: '1' },
		{ status: 'onbackorder', count: '5' },
	],
};

jest.mock( '@woocommerce/base-context/hooks', () => {
	return {
		...jest.requireActual( '@woocommerce/base-context/hooks' ),
		useCollectionData: () => ( { isLoading: false, results: mockResults } ),
	};
} );

const StockFilterBlock = ( props ) => <Block { ...props } />;
describe( 'Testing stock filter', () => {
	beforeEach( () => {
		allSettings.stockStatusOptions = {
			instock: 'In stock',
			outofstock: 'Out of stock',
			onbackorder: 'On backorder',
		};
	} );

	afterEach( () => {
		fetchMock.resetMocks();
	} );

	it( 'renders the stock filter block', async () => {
		const { container } = render(
			<StockFilterBlock attributes={ { isPreview: false } } />
		);
		expect( container ).toMatchSnapshot();
	} );

	it( 'renders the stock filter block with the filter button', async () => {
		const { container } = render(
			<StockFilterBlock
				attributes={ { isPreview: false, showFilterButton: true } }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );

	it( 'renders the stock filter block with the product counts', async () => {
		const { container } = render(
			<StockFilterBlock
				attributes={ {
					isPreview: false,
					showCounts: true,
				} }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );
} );
