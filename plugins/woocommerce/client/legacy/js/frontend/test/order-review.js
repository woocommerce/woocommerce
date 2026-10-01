/**
 * @jest-environment jest-fixed-jsdom
 */

function TestFormData( form ) {
	this.form = form;
}

describe( 'Review Order frontend behavior', () => {
	let originalFetch;
	let originalGlobalFetch;
	let originalFormData;
	let originalWcOrderReview;
	let originalReadyState;

	function initializeReviewOrder() {
		originalReadyState = Object.getOwnPropertyDescriptor(
			document,
			'readyState'
		);
		Object.defineProperty( document, 'readyState', {
			configurable: true,
			value: 'complete',
		} );

		jest.resetModules();
		require( '../order-review' );

		if ( originalReadyState ) {
			Object.defineProperty( document, 'readyState', originalReadyState );
		} else {
			delete document.readyState;
		}
	}

	beforeEach( () => {
		originalFetch = window.fetch;
		originalGlobalFetch = global.fetch;
		originalFormData = window.FormData;
		originalWcOrderReview = window.wcOrderReview;
	} );

	afterEach( () => {
		document.body.replaceChildren();
		jest.resetModules();
		jest.restoreAllMocks();
		window.fetch = originalFetch;
		global.fetch = originalGlobalFetch;
		window.FormData = originalFormData;

		if ( undefined === originalWcOrderReview ) {
			delete window.wcOrderReview;
		} else {
			window.wcOrderReview = originalWcOrderReview;
		}
	} );

	test( 'dismisses disabled-products notice with the production hidden class', () => {
		document.body.innerHTML = `
			<div class="woocommerce-review-order__notice">
				<button class="woocommerce-review-order__notice-dismiss" type="button">Dismiss</button>
			</div>
		`;

		initializeReviewOrder();

		const notice = document.querySelector(
			'.woocommerce-review-order__notice'
		);
		notice
			.querySelector( '.woocommerce-review-order__notice-dismiss' )
			.click();

		expect(
			notice.classList.contains(
				'woocommerce-review-order__notice--hidden'
			)
		).toBe( true );
	} );

	test( 'blocks a text-only review until a rating is selected', async () => {
		const ajaxUrl = 'https://example.test/wp-admin/admin-ajax.php';
		const ratingRequired = 'A rating is required before submitting.';
		window.wcOrderReview = {
			i18n: { rating_required: ratingRequired },
		};
		window.fetch = jest.fn( () =>
			Promise.resolve( {
				json: () =>
					Promise.resolve( { success: true, data: { results: {} } } ),
			} )
		);
		global.fetch = window.fetch;
		window.FormData = TestFormData;
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" data-ajax-url="${ ajaxUrl }">
				<div class="woocommerce-review-order__item" data-initial-rating="0" data-initial-text="">
					<h3 class="woocommerce-review-order__item-title">Product</h3>
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="5 stars" />
					</div>
					<textarea class="woocommerce-review-order__item-review-textarea" name="review"></textarea>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const rating = document.querySelector(
			'.woocommerce-star-rating__input'
		);
		const textarea = form.querySelector(
			'.woocommerce-review-order__item-review-textarea'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);

		expect( submit.disabled ).toBe( true );

		textarea.value = 'Text-only review';
		textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

		expect( submit.disabled ).toBe( false );
		submit.click();

		expect( form.querySelectorAll( '[role="alert"]' ) ).toHaveLength( 1 );
		expect( form.querySelector( '[role="alert"]' ).textContent ).toBe(
			ratingRequired
		);
		expect( window.fetch ).not.toHaveBeenCalled();

		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect( form.querySelectorAll( '[role="alert"]' ) ).toHaveLength( 0 );
		expect( submit.disabled ).toBe( false );

		submit.click();

		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		expect( window.fetch ).toHaveBeenCalledWith(
			ajaxUrl,
			expect.objectContaining( { method: 'POST' } )
		);

		await Promise.resolve();
	} );
} );
