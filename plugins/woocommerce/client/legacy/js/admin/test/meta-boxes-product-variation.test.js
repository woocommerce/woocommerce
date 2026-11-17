describe( 'Add prices across variation pages', () => {
	let $;
	let products;
	let requests;
	let pending;
	let failSave;
	let failLoad;
	let validationError;
	let pageSize;

	const render = ( page ) =>
		products
			.slice( ( page - 1 ) * pageSize, page * pageSize )
			.map(
				( product, index ) => `
		<div class="woocommerce_variation">
			<input name="variable_post_id[${ index }]" value="${ product.id }">
			<input class="wc_input_price" name="variable_regular_price[${ index }]" value="${ product.price }">
			<input class="wc_input_price" name="variable_subscription_sign_up_fee[${ index }]" value="${ product.fee }">
			<input name="variable_sku[${ index }]" value="${ product.sku }">
		</div>
	`
			)
			.join( '' );

	beforeEach( () => {
		jest.useFakeTimers();
		jest.resetModules();
		$ = require( 'jquery' );
		global.jQuery = $;
		global.wp = { media: { model: { settings: { post: { id: 100 } } } } };
		global.woocommerce_admin_meta_boxes_variations = {
			post_id: 100,
			variations_per_page: 15,
			i18n_add_prices_failed: 'Some prices could not be added.',
			i18n_variation_count_single: '1 variation',
			i18n_variation_count_plural: '%qty% variations',
		};
		pageSize = 15;
		products = Array.from( { length: 27 }, ( _, i ) => ( {
			id: i + 1,
			price: '',
			fee: '',
			sku: '',
		} ) );
		requests = [];
		pending = null;
		failSave = false;
		failLoad = false;
		validationError = '';
		window.alert = jest.fn();
		for ( const method of [
			'block',
			'unblock',
			'tipTip',
			'datepicker',
			'sortable',
			'WCBackboneModal',
		] ) {
			$.fn[ method ] = jest.fn().mockReturnThis();
		}
		$.fn.serializeJSON = function () {
			const data = {};
			this.serializeArray().forEach( ( { name, value } ) => {
				const [ , field, index ] = name.match( /(.+)\[(\d+)\]/ );
				data[ field ] = data[ field ] || {};
				data[ field ][ index ] = value;
			} );
			return data;
		};
		$.ajax = jest.fn( ( options ) => {
			const deferred = $.Deferred();
			requests.push( options.data );
			const respond = () => {
				const isSave =
					options.data.action === 'woocommerce_save_variations';
				if ( ( isSave && failSave ) || ( ! isSave && failLoad ) ) {
					if ( options.error ) {
						options.error( {}, 'error', 'Failed' );
					}
					deferred.reject();
					return;
				}
				let response;
				if ( isSave ) {
					if ( ! validationError ) {
						Object.entries( options.data.variable_post_id ).forEach(
							( [ index, id ] ) => {
								const product = products.find(
									( item ) => item.id === Number( id )
								);
								product.price =
									options.data.variable_regular_price[
										index
									];
								product.fee =
									options.data.variable_subscription_sign_up_fee[
										index
									];
								product.sku =
									options.data.variable_sku[ index ];
							}
						);
					}
					response = validationError;
				} else {
					response = render( options.data.page );
				}
				options.success( response );
				deferred.resolve( response );
			};
			pending = respond;
			return deferred.promise();
		} );
		document.body.innerHTML = `
			<select id="product-type"><option>variable</option><option>variable-subscription</option></select>
			<div id="woocommerce-product-data"><div id="variable_product_options">
				<button class="add_price_for_variations"></button>
				<div class="woocommerce_variations" data-page="1" data-total_pages="2" data-total="27">${ render(
					1
				) }</div>
				<div class="variations-pagenav"><select class="page-selector"><option>1</option><option>2</option></select></div>
			</div></div>
			<input class="wc_input_variations_price" value="25">
			<button class="add_variations_price_button"></button>
		`;
		require( '../meta-boxes-product-variation' );
		jest.runAllTimers();
		jest.useRealTimers();
	} );

	afterEach( () => {
		$( document ).off();
		$( document.body ).off();
		document.body.innerHTML = '';
		delete global.jQuery;
		delete global.wp;
		delete global.woocommerce_admin_meta_boxes_variations;
	} );

	const start = () => {
		$( '.add_price_for_variations' ).trigger( 'click' );
		return $( '.add_variations_price_button' ).triggerHandler( 'click' );
	};

	const finish = async () => {
		for ( let i = 0; i < 20; i++ ) {
			if ( pending ) {
				const respond = pending;
				pending = null;
				respond();
			}
			await Promise.resolve();
		}
	};

	test.each( [ 1, 2 ] )(
		'fills all 27 variations starting on page %i and restores that page',
		async ( page ) => {
			$( '.woocommerce_variations' )
				.attr( 'data-page', page )
				.html( render( page ) );
			const done = start();
			await finish();
			await done;
			expect(
				products.every( ( product ) => product.price === '25' )
			).toBe( true );
			expect( $( '.woocommerce_variations' ).attr( 'data-page' ) ).toBe(
				String( page )
			);
			expect( $( '.page-selector' ).val() ).toBe( String( page ) );
			expect( window.alert ).not.toHaveBeenCalled();
		}
	);

	test( 'preserves existing prices and the exact empty/zero predicate on every page', async () => {
		const prices = [ '', '0', '0.00', '0,00', '12.50' ];
		products.forEach( ( product, i ) => {
			product.price = prices[ i % prices.length ];
		} );
		$( '.woocommerce_variations' ).html( render( 1 ) );
		const done = start();
		await finish();
		await done;
		products.forEach( ( product, i ) => {
			const original = prices[ i % prices.length ];
			expect( product.price ).toBe(
				original === '' || original === '0' ? '25' : original
			);
		} );
	} );

	test( 'waits for pending edits to save before loading another page', async () => {
		$( '[name="variable_sku[0]"]' )
			.val( 'pending-sku' )
			.trigger( 'change' );
		const done = start();
		expect( requests.map( ( request ) => request.action ) ).toEqual( [
			'woocommerce_save_variations',
		] );
		await Promise.resolve();
		expect( requests ).toHaveLength( 1 );
		await finish();
		await done;
		expect( products[ 0 ].sku ).toBe( 'pending-sku' );
	} );

	test( 'continues past a page with no eligible prices', async () => {
		products.slice( 0, 15 ).forEach( ( product ) => {
			product.price = '50';
		} );
		$( '.woocommerce_variations' ).html( render( 1 ) );
		const done = start();
		await finish();
		await done;
		expect(
			products
				.slice( 0, 15 )
				.every( ( product ) => product.price === '50' )
		).toBe( true );
		expect(
			products.slice( 15 ).every( ( product ) => product.price === '25' )
		).toBe( true );
	} );

	test( 'uses the configured page count and localized input', async () => {
		pageSize = 10;
		global.woocommerce_admin_meta_boxes_variations.variations_per_page =
			pageSize;
		$( '.woocommerce_variations' )
			.attr( 'data-total_pages', 3 )
			.html( render( 1 ) );
		$( '.wc_input_variations_price' ).val( '12,50' );
		const done = start();
		await finish();
		await done;
		expect(
			products.every( ( product ) => product.price === '12,50' )
		).toBe( true );
	} );

	test( 'keeps the subscription sign-up fee target', async () => {
		$( '#product-type' ).val( 'variable-subscription' );
		const done = start();
		await finish();
		await done;
		expect(
			products.every(
				( product ) => product.fee === '25' && product.price === ''
			)
		).toBe( true );
	} );

	test.each( [ 'transport', 'validation', 'load' ] )(
		'stops and reports a %s failure',
		async ( failure ) => {
			failSave = failure === 'transport';
			failLoad = failure === 'load';
			validationError =
				failure === 'validation'
					? '<div id="woocommerce_errors">Invalid price</div>'
					: '';
			const done = start();
			await finish();
			await done;
			expect( window.alert ).toHaveBeenCalledTimes( 1 );
			expect(
				products
					.slice( 15 )
					.every( ( product ) => product.price === '' )
			).toBe( true );
			expect(
				$( '#variable_product_options' ).data( 'adding-prices' )
			).toBeUndefined();
			if ( failure === 'validation' ) {
				expect( $( '#woocommerce_errors' ).text() ).toBe(
					'Invalid price'
				);
				expect( $( '.variation-needs-update' ) ).toHaveLength( 15 );
			}
		}
	);

	test( 'ignores duplicate submissions while prices are being added', async () => {
		const done = start();
		$( '.add_variations_price_button' ).triggerHandler( 'click' );
		expect( requests ).toHaveLength( 1 );
		await finish();
		await done;
	} );
} );
