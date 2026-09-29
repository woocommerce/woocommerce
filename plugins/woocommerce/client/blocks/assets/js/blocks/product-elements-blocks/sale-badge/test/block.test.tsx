import { render, screen } from '@testing-library/react';

import { Block } from '../block';

const product = {
	id: 1,
	on_sale: true,
	type: 'variable',
	prices: {
		regular_price: '1000',
		price: '900',
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
	},
};

jest.mock( '@woocommerce/shared-context', () => ( {
	useInnerBlockLayoutContext: () => ( { parentClassName: '' } ),
	useProductDataContext: () => ( { product } ),
} ) );
jest.mock( '@woocommerce/shared-hocs', () => ( {
	withProductDataContext: ( Component: unknown ) => Component,
} ) );
jest.mock( '@woocommerce/base-hooks', () => ( {
	useStyleProps: () => ( { className: '', style: {} } ),
} ) );

it( 'previews a variable discount using parent prices without rendering on the server', () => {
	product.prices.price = '900';
	const { rerender } = render(
		<Block
			productId={ 1 }
			align={ false }
			isDescendentOfSingleProductTemplate={ false }
			badgeContent="percentage"
		/>
	);
	expect( screen.getByText( 'Up to 10%' ) ).toBeInTheDocument();

	product.prices.price = '1000';
	rerender(
		<Block
			productId={ 1 }
			align={ false }
			isDescendentOfSingleProductTemplate={ false }
			badgeContent="percentage"
		/>
	);
	expect( screen.getByText( 'Sale' ) ).toBeInTheDocument();
} );

it( 'formats the variable amount preview with currency and custom text', () => {
	product.prices.price = '900';
	render(
		<Block
			productId={ 1 }
			align={ false }
			isDescendentOfSingleProductTemplate={ false }
			badgeContent="amount"
			prefix="Save "
			suffix=" off"
		/>
	);
	expect( screen.getByText( 'Save Up to $1.00 off' ) ).toBeInTheDocument();
} );
