import { render, screen } from '@testing-library/react';
import { getLocaleData, resetLocaleData, setLocaleData } from '@wordpress/i18n';

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

beforeEach( () => {
	product.type = 'variable';
	product.prices.regular_price = '1000';
	product.prices.price = '900';
} );

it.each( [
	[ '', 'Sale' ],
	[ '0', '0' ],
] )(
	'uses the text fallback only for empty custom text (%s)',
	( saleText, expected ) => {
		render(
			<Block
				productId={ 1 }
				align={ false }
				isDescendentOfSingleProductTemplate={ false }
				badgeContent="text"
				saleText={ saleText }
			/>
		);
		expect( screen.getByText( expected ) ).toBeInTheDocument();
	}
);

it.each< [ string, string, string | null ] >( [
	[ 'simple', '99600', null ],
	[ 'simple', '99500', '1%' ],
] )(
	'rounds the %s percentage preview at price %s',
	( type, price, expected ) => {
		product.type = type;
		product.prices.regular_price = '100000';
		product.prices.price = price;
		const { container } = render(
			<Block
				productId={ 1 }
				align={ false }
				isDescendentOfSingleProductTemplate={ false }
				badgeContent="percentage"
			/>
		);
		expect( container.textContent ).toBe(
			expected === null
				? ''
				: `${ expected }Product on sale: ${ expected }`
		);
	}
);

it( 'keeps amount badges for discounts whose percentage rounds to zero', () => {
	product.prices.regular_price = '100000';
	product.prices.price = '99600';
	render(
		<Block
			productId={ 1 }
			align={ false }
			isDescendentOfSingleProductTemplate={ false }
			badgeContent="amount"
		/>
	);
	expect( screen.getByText( 'Up to $4.00' ) ).toBeInTheDocument();
} );

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

it( 'preserves currency units without custom affixes when using Up to', () => {
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
	expect( screen.getByText( 'Up to $1.00' ) ).toBeInTheDocument();
} );

it.each( [
	[ 'simple', 'Save 10% off' ],
	[ 'variable', 'Up to 10%' ],
] )(
	'formats the %s percentage label with custom affixes',
	( type, expected ) => {
		product.type = type;
		render(
			<Block
				productId={ 1 }
				align={ false }
				isDescendentOfSingleProductTemplate={ false }
				badgeContent="percentage"
				prefix="Save "
				suffix=" off"
			/>
		);
		expect( screen.getByText( expected ) ).toBeInTheDocument();
		expect(
			screen.getByText( `Product on sale: ${ expected }` )
		).toBeInTheDocument();
	}
);

it( 'uses localized spacing before the percent sign', () => {
	const localeData = getLocaleData( 'woocommerce' );
	setLocaleData( { '%s%%': [ '%s\u00a0%%' ] }, 'woocommerce' );
	try {
		render(
			<Block
				productId={ 1 }
				align={ false }
				isDescendentOfSingleProductTemplate={ false }
				badgeContent="percentage"
			/>
		);
		expect(
			screen.getByText( 'Up to 10\u00a0%', {
				normalizer: ( text ) => text,
			} )
		).toBeInTheDocument();
	} finally {
		resetLocaleData( localeData, 'woocommerce' );
	}
} );
