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
		// No tabindex on the heading, as an older theme override would have.
		document.body.innerHTML = `
			<div class="woocommerce-review-order">
				<h1 class="woocommerce-review-order__title">Review your order</h1>
				<div class="woocommerce-review-order__notice">
					<button class="woocommerce-review-order__notice-dismiss" type="button">Dismiss</button>
				</div>
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
		expect( document.activeElement ).toBe(
			document.querySelector( '.woocommerce-review-order__title' )
		);
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
					<p id="rating-error-1" class="woocommerce-review-order__item-rating-error"></p>
					<div class="woocommerce-star-rating" aria-describedby="rating-caption-1">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="4" data-label="Good" />
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="3" data-label="Average" />
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="2" data-label="Not that bad" />
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="1" data-label="Very poor" />
						<span id="rating-caption-1" class="woocommerce-star-rating__caption"></span>
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
			'.woocommerce-star-rating__input[value="5"]'
		);
		const textarea = form.querySelector(
			'.woocommerce-review-order__item-review-textarea'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);

		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );

		textarea.value = 'Text-only review';
		textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'false' );
		submit.click();

		const message = form.querySelector(
			'.woocommerce-review-order__item-rating-error'
		);
		expect( message.textContent ).toBe( ratingRequired );
		expect( window.fetch ).not.toHaveBeenCalled();
		// The element the template rendered, not a new one.
		expect( message.id ).toBe( 'rating-error-1' );
		expect( message.hasAttribute( 'role' ) ).toBe( false );

		// Reachable from the radio that takes focus, not from the wrapper.
		const lowest = form.querySelector(
			'.woocommerce-star-rating__input[value="1"]'
		);
		expect( lowest.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( lowest.getAttribute( 'aria-describedby' ) ).toContain(
			message.id
		);
		expect( document.activeElement ).toBe( lowest );

		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		// Kept in the DOM and reused for the next message.
		expect(
			form.querySelectorAll(
				'.woocommerce-review-order__item-rating-error'
			)
		).toHaveLength( 1 );
		expect( message.textContent ).toBe( '' );
		expect( lowest.hasAttribute( 'aria-invalid' ) ).toBe( false );
		expect( lowest.getAttribute( 'aria-describedby' ) || '' ).not.toContain(
			message.id
		);
		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'false' );

		submit.click();

		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		expect( window.fetch ).toHaveBeenCalledWith(
			ajaxUrl,
			expect.objectContaining( { method: 'POST' } )
		);

		await Promise.resolve();
	} );

	test( 'keeps the submit button reachable while gating it', () => {
		const ajaxUrl = 'https://example.test/wp-admin/admin-ajax.php';
		window.fetch = jest.fn();
		global.fetch = window.fetch;
		window.FormData = TestFormData;
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" data-ajax-url="${ ajaxUrl }">
				<div class="woocommerce-review-order__item" data-initial-rating="0" data-initial-text="">
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="5 stars" />
					</div>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const submit = document.querySelector(
			'.woocommerce-review-order__submit'
		);

		// `disabled` would drop it out of the tab order with no explanation.
		expect( submit.disabled ).toBe( false );
		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );

		submit.click();

		expect( window.fetch ).not.toHaveBeenCalled();
	} );

	test( 'blocks a gated submit on a form the AJAX handler skipped', () => {
		// No `data-ajax-url`, so `initAjaxSubmit` bails and the gate's own
		// handler is all that stands between a dimmed button and a POST.
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" method="post" action="https://example.test/wp-admin/admin-ajax.php">
				<div class="woocommerce-review-order__item" data-initial-rating="0" data-initial-text="">
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					</div>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);
		// Records whether the gate cancelled first, then cancels so jsdom
		// does not try to navigate.
		const preventedByGate = [];
		const submitted = jest.fn( ( event ) => {
			preventedByGate.push( event.defaultPrevented );
			event.preventDefault();
		} );
		form.addEventListener( 'submit', submitted );

		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );
		submit.click();
		expect( submitted ).not.toHaveBeenCalled();

		// Once the gate opens the form submits as it always did.
		const rating = form.querySelector( '.woocommerce-star-rating__input' );
		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'false' );
		submit.click();
		expect( submitted ).toHaveBeenCalledTimes( 1 );
		expect( preventedByGate ).toEqual( [ false ] );
	} );

	test( 'blocks a gated requestSubmit, which raises no click', () => {
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" method="post" action="https://example.test/wp-admin/admin-ajax.php">
				<div class="woocommerce-review-order__item" data-initial-rating="0" data-initial-text="">
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					</div>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);
		const prevented = [];
		form.addEventListener( 'submit', ( event ) => {
			prevented.push( event.defaultPrevented );
			event.preventDefault();
		} );

		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );
		form.requestSubmit();

		// The click guard cannot see this path, so the form's own guard is
		// what has to cancel it.
		expect( prevented ).toEqual( [ true ] );

		const rating = form.querySelector( '.woocommerce-star-rating__input' );
		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		form.requestSubmit();
		expect( prevented ).toEqual( [ true, false ] );
	} );

	test( 'registers both row regions up front for an override that drops them', () => {
		// A row template copied before the paragraphs shipped.
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" method="post" action="https://example.test/wp-admin/admin-ajax.php">
				<div class="woocommerce-review-order__item" data-row-index="0" data-initial-rating="0" data-initial-text="">
					<p class="woocommerce-review-order__item-title">CRR Product</p>
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					</div>
					<textarea class="woocommerce-review-order__item-review-textarea"></textarea>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const row = document.querySelector(
			'.woocommerce-review-order__item'
		);
		const status = row.querySelector(
			'.woocommerce-review-order__item-status'
		);
		const error = row.querySelector(
			'.woocommerce-review-order__item-rating-error'
		);

		// A live region first created when its text is written is not
		// announced, so both have to exist and be empty before any write.
		expect( status.getAttribute( 'role' ) ).toBe( 'status' );
		expect( status.textContent ).toBe( '' );
		expect( error ).not.toBeNull();
		expect( error.textContent ).toBe( '' );
		// The error text is deliberately not announced on its own, so the
		// paragraph must carry no role at all.
		expect( error.hasAttribute( 'role' ) ).toBe( false );
	} );

	test.each( [
		[ 'no role', '', 'status' ],
		// An empty token list leaves the element without a role.
		[ 'an empty role', ' role=""', 'status' ],
		[ 'a blank role', ' role=" "', 'status' ],
		[ 'the shipped role', ' role="status"', 'status' ],
		[ 'its own role', ' role="alert"', 'alert' ],
	] )(
		'announces an override status paragraph that ships with %s',
		( label, roleAttr, expected ) => {
			document.body.innerHTML = `
				<form class="woocommerce-review-order__form" method="post" action="https://example.test/wp-admin/admin-ajax.php">
					<div class="woocommerce-review-order__item" data-row-index="0" data-initial-rating="0" data-initial-text="">
						<div class="woocommerce-star-rating">
							<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
						</div>
						<p class="woocommerce-review-order__item-status"${ roleAttr }></p>
					</div>
					<button class="woocommerce-review-order__submit" type="submit">Submit</button>
				</form>
			`;

			initializeReviewOrder();

			const regions = document.querySelectorAll(
				'.woocommerce-review-order__item-status'
			);
			// Reused, not duplicated beside the override's own.
			expect( regions ).toHaveLength( 1 );
			expect( regions[ 0 ].getAttribute( 'role' ) ).toBe( expected );
		}
	);

	test( 'explains a gated submit with a hint the button points at', () => {
		window.wcOrderReview = {
			i18n: { submit_hint: 'Rate or review something first.' },
		};
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" method="post" action="https://example.test/wp-admin/admin-ajax.php">
				<div class="woocommerce-review-order__item" data-initial-rating="0" data-initial-text="">
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					</div>
				</div>
				<div class="woocommerce-review-order__actions">
					<button class="woocommerce-review-order__submit" type="submit">Submit</button>
				</div>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);
		const hint = form.querySelector(
			'.woocommerce-review-order__submit-hint'
		);

		expect( hint.parentElement ).toBe( submit.parentElement );
		expect( hint.textContent ).toBe( 'Rate or review something first.' );
		expect( hint.hidden ).toBe( false );
		expect(
			submit.getAttribute( 'aria-describedby' ).split( /\s+/ )
		).toContain( hint.id );

		const rating = form.querySelector( '.woocommerce-star-rating__input' );
		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		// Nothing left to explain once the button works.
		expect( hint.hidden ).toBe( true );
		expect( submit.hasAttribute( 'aria-describedby' ) ).toBe( false );
	} );

	test( 'keeps the hint quiet on an override that renders no rows', () => {
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" method="post" action="https://example.test/wp-admin/admin-ajax.php">
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);
		const hint = form.querySelector(
			'.woocommerce-review-order__submit-hint'
		);

		// Nothing to add or change, so the gate stays shut and the hint must
		// not name an action the page does not offer.
		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );
		expect( hint.hidden ).toBe( true );
		expect( submit.hasAttribute( 'aria-describedby' ) ).toBe( false );
	} );

	test( 'moves focus into the thank-you view once the form is hidden', async () => {
		const ajaxUrl = 'https://example.test/wp-admin/admin-ajax.php';
		window.fetch = jest.fn( () =>
			Promise.resolve( {
				json: () =>
					Promise.resolve( {
						success: true,
						data: { results: { 0: { status: 'ok' } } },
					} ),
			} )
		);
		global.fetch = window.fetch;
		window.FormData = TestFormData;
		document.body.innerHTML = `
			<div class="woocommerce-review-order">
				<form class="woocommerce-review-order__form" data-ajax-url="${ ajaxUrl }">
					<div class="woocommerce-review-order__item" data-row-index="0" data-initial-rating="0" data-initial-text="">
						<div class="woocommerce-star-rating">
							<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="5 stars" />
						</div>
						<p id="row-status-1" class="woocommerce-review-order__item-status" role="status"></p>
					</div>
					<button class="woocommerce-review-order__submit" type="submit">Submit</button>
				</form>
				<div class="woocommerce-review-order__success" hidden>
					<h1 class="woocommerce-review-order__empty-title">Thank you</h1>
				</div>
			</div>
		`;

		initializeReviewOrder();

		const rating = document.querySelector(
			'.woocommerce-star-rating__input'
		);
		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		document.querySelector( '.woocommerce-review-order__submit' ).click();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		const success = document.querySelector(
			'.woocommerce-review-order__success'
		);
		expect( success.hidden ).toBe( false );
		// The heading, not the unnamed container. The fixture leaves the
		// tabindex off, as an older theme override would.
		expect( document.activeElement ).toBe( success.querySelector( 'h1' ) );

		// The region the template rendered: a replaced node would be a fresh
		// live region and go unannounced.
		const status = document.querySelector(
			'.woocommerce-review-order__item-status'
		);
		expect( status.id ).toBe( 'row-status-1' );
		expect( status.textContent ).not.toBe( '' );
	} );

	test( 'sends one request for a double click and reopens the gate after a failure', async () => {
		const ajaxUrl = 'https://example.test/wp-admin/admin-ajax.php';
		let reject;
		window.fetch = jest.fn(
			() =>
				new Promise( ( resolve, rejectFn ) => {
					reject = rejectFn;
				} )
		);
		global.fetch = window.fetch;
		window.FormData = TestFormData;
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" data-ajax-url="${ ajaxUrl }">
				<div class="woocommerce-review-order__item" data-row-index="0" data-initial-rating="0" data-initial-text="">
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					</div>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);
		const rating = form.querySelector( '.woocommerce-star-rating__input' );
		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		submit.click();
		submit.click();
		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );

		reject( new Error( 'network' ) );
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		// A failed request has to leave the customer able to try again.
		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'false' );
	} );

	test( 'keeps the gate shut while a request is in flight', () => {
		const ajaxUrl = 'https://example.test/wp-admin/admin-ajax.php';
		window.fetch = jest.fn( () => new Promise( () => {} ) );
		global.fetch = window.fetch;
		window.FormData = TestFormData;
		document.body.innerHTML = `
			<form class="woocommerce-review-order__form" data-ajax-url="${ ajaxUrl }">
				<div class="woocommerce-review-order__item" data-row-index="0" data-initial-rating="0" data-initial-text="">
					<div class="woocommerce-star-rating">
						<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					</div>
					<textarea class="woocommerce-review-order__item-review-textarea"></textarea>
				</div>
				<button class="woocommerce-review-order__submit" type="submit">Submit</button>
			</form>
		`;

		initializeReviewOrder();

		const form = document.querySelector(
			'.woocommerce-review-order__form'
		);
		const submit = form.querySelector(
			'.woocommerce-review-order__submit'
		);
		const rating = form.querySelector( '.woocommerce-star-rating__input' );
		const textarea = form.querySelector( 'textarea' );
		rating.checked = true;
		rating.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		submit.click();
		expect( window.fetch ).toHaveBeenCalledTimes( 1 );

		// The dirty check alone would reopen the gate on this keystroke and
		// advertise a button that the AJAX handler goes on refusing.
		textarea.value = 'Still typing.';
		textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		expect( submit.getAttribute( 'aria-disabled' ) ).toBe( 'true' );

		// The row is edited, so the hint would name the wrong reason here.
		expect(
			form.querySelector( '.woocommerce-review-order__submit-hint' )
				.hidden
		).toBe( true );
	} );

	test.each( [
		[ 'ltr', '4' ],
		[ 'rtl', '2' ],
	] )(
		'follows the visual star order for ArrowRight in %s',
		( direction, expected ) => {
			document.body.innerHTML = `
				<div class="woocommerce-star-rating" style="direction: ${ direction };">
					<input class="woocommerce-star-rating__input" name="rating" type="radio" value="5" data-label="Perfect" />
					<input class="woocommerce-star-rating__input" name="rating" type="radio" value="4" data-label="Good" />
					<input class="woocommerce-star-rating__input" name="rating" type="radio" value="3" data-label="Average" />
					<input class="woocommerce-star-rating__input" name="rating" type="radio" value="2" data-label="Not that bad" />
					<input class="woocommerce-star-rating__input" name="rating" type="radio" value="1" data-label="Very poor" />
				</div>
			`;

			initializeReviewOrder();

			// DOM order is 5..1, and RTL flips the row again.
			const third = document.querySelector(
				'.woocommerce-star-rating__input[value="3"]'
			);
			third.checked = true;
			third.focus();
			third.dispatchEvent(
				new window.KeyboardEvent( 'keydown', {
					key: 'ArrowRight',
					bubbles: true,
				} )
			);

			expect(
				document.querySelector(
					'.woocommerce-star-rating__input:checked'
				).value
			).toBe( expected );
		}
	);
} );
