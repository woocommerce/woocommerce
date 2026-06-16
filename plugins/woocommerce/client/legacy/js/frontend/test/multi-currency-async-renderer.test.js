const {
	MultiCurrencyAsyncPriceRenderer,
} = require( '../multi-currency-async-renderer' );

describe( 'MultiCurrencyAsyncPriceRenderer', () => {
	let renderer;

	const mockConfig = {
		default_currency: 'USD',
		selected_currency: 'EUR',
		charm_only_products: true,
		currencies: {
			USD: {
				code: 'USD',
				symbol: '$',
				rate: 1,
				decimals: 2,
				decimal_sep: '.',
				thousand_sep: ',',
				symbol_pos: 'left',
				rounding: 0,
				charm: 0,
			},
			EUR: {
				code: 'EUR',
				symbol: '€',
				rate: 0.85,
				decimals: 2,
				decimal_sep: ',',
				thousand_sep: '.',
				symbol_pos: 'right_space',
				rounding: 1,
				charm: -0.01,
			},
		},
	};

	beforeEach( () => {
		document.body.textContent = '';
		window.wcpayAsyncPriceConfig = {
			apiUrl:
				'https://example.test/wp-json/wc/v3/payments/multi-currency/public/config',
			defaultCurrency: mockConfig.currencies.USD,
			srText: {
				sale_original: 'Original price was: %s.',
				sale_current: 'Current price is: %s.',
				range: 'Price range: %1$s through %2$s',
			},
		};

		renderer = new MultiCurrencyAsyncPriceRenderer();
		renderer.config = mockConfig;
	} );

	afterEach( () => {
		renderer.destroy();
		delete window.wcpayAsyncPriceConfig;
	} );

	test( 'converts skeleton prices into WooCommerce price markup', () => {
		const wrapper = document.createElement( 'span' );
		wrapper.className =
			'woocommerce-Price-amount amount wcpay-async-price';
		wrapper.setAttribute( 'data-wcpay-price', '10.00' );
		wrapper.setAttribute( 'data-wcpay-price-type', 'product' );
		wrapper.innerHTML =
			'<bdi class="wcpay-price-skeleton"></bdi><span class="screen-reader-text wcpay-price-placeholder">$10.00</span>';
		document.body.appendChild( wrapper );

		renderer.convertAllPrices();

		const bdi = wrapper.querySelector( 'bdi' );

		expect( wrapper.classList.contains( 'wcpay-price-converted' ) ).toBe(
			true
		);
		expect( wrapper.querySelector( '.wcpay-price-skeleton' ) ).toBeNull();
		expect(
			wrapper.querySelector( '.wcpay-price-placeholder' )
		).toBeNull();
		expect( bdi.textContent ).toBe( '8,99 €' );
		expect(
			bdi.querySelector( '.woocommerce-Price-currencySymbol' )
				.textContent
		).toBe( '€' );
	} );

	test( 'updates annotated sale and range screen reader text', () => {
		document.body.innerHTML = `
			<span class="screen-reader-text" data-wcpay-sr-type="sale_original" data-wcpay-sr-price="50">Original price was: $50.00.</span>
			<span class="screen-reader-text" data-wcpay-sr-type="sale_current" data-wcpay-sr-price="35">Current price is: $35.00.</span>
				<span class="screen-reader-text" data-wcpay-sr-type="range"
					data-wcpay-sr-price-from="10" data-wcpay-sr-price-to="30">Price range: $10.00 through $30.00</span>
		`;

		renderer.convertScreenReaderText();

		const saleOriginal = document.querySelector(
			'[data-wcpay-sr-type="sale_original"]'
		);
		const saleCurrent = document.querySelector(
			'[data-wcpay-sr-type="sale_current"]'
		);
		const range = document.querySelector(
			'[data-wcpay-sr-type="range"]'
		);

		expect( saleOriginal.textContent ).toBe(
			'Original price was: 42,99 €.'
		);
		expect( saleCurrent.textContent ).toBe(
			'Current price is: 29,99 €.'
		);
		expect( range.textContent ).toBe(
			'Price range: 8,99 € through 25,99 €'
		);
		expect(
			saleOriginal.classList.contains( 'wcpay-sr-converted' )
		).toBe( true );
	} );

	test( 'formats default-currency prices on fetch failure when fallback config exists', () => {
		const wrapper = document.createElement( 'span' );
		wrapper.setAttribute( 'data-wcpay-price', '10.00' );
		wrapper.innerHTML =
			'<bdi class="wcpay-price-skeleton"></bdi><span class="screen-reader-text wcpay-price-placeholder">$10.00</span>';
		document.body.appendChild( wrapper );

		renderer.showErrorState();

		expect( wrapper.classList.contains( 'wcpay-price-converted' ) ).toBe(
			true
		);
		expect( wrapper.querySelector( '.wcpay-price-placeholder' ) ).toBeNull();
		expect( wrapper.querySelector( 'bdi' ).textContent ).toBe( '$10.00' );
	} );

	test( 'keeps screen reader fallback text when no default-currency config exists', () => {
		delete window.wcpayAsyncPriceConfig.defaultCurrency;

		const wrapper = document.createElement( 'span' );
		wrapper.setAttribute( 'data-wcpay-price', '10.00' );
		wrapper.innerHTML =
			'<bdi class="wcpay-price-skeleton"></bdi><span class="screen-reader-text wcpay-price-placeholder">$10.00</span>';
		document.body.appendChild( wrapper );

		renderer.showErrorState();

		expect(
			wrapper.querySelector( '.wcpay-price-placeholder' )
		).not.toBeNull();
		expect( wrapper.querySelector( '.wcpay-price-error' ).textContent ).toBe(
			'—'
		);
	} );
} );
