describe( 'Live departures board', () => {
	let hidden;
	let reducedMotion;
	let animate;
	const originalFetch = global.fetch;
	const originalHidden = Object.getOwnPropertyDescriptor( document, 'hidden' );
	const originalMatchMedia = window.matchMedia;
	const originalAnimate = Element.prototype.animate;

	function row( id, label ) {
		return `<tr data-action-id="${ id }"><td><a href="#action-${ id }">Action ${ id }</a></td>` +
			`<td><span class="wc-departures__flap">${ label }</span></td></tr>`;
	}

	function html( rows, stamp = '10:00' ) {
		return `<div class="wc-departures" data-refresh-url="/wp-admin/admin-ajax.php?nonce=fresh"
			data-message-refreshing="Updating" data-message-updated="Updated" data-message-failed="Failed">
			<h1><span class="wc-departures__flap">D</span></h1>
			<div class="wc-departures__snapshot">${ stamp }</div>
			<p class="wc-departures__live-message"></p><a class="wc-departures__refresh" href="#refresh">Refresh</a>
			<table><tbody>${ rows }</tbody></table><div class="wc-departures__empty" ${ rows ? 'hidden' : '' }>Empty</div>
			<footer class="wc-departures__footer"><a href="#list">Action list</a></footer></div>`;
	}

	function response( rows, stamp ) {
		return { ok: true, json: async () => ( { success: true, data: { html: html( rows, stamp ) } } ) };
	}

	function start( markup = html( row( 1, 'Scheduled' ) ) ) {
		document.body.innerHTML = markup;
		jest.isolateModules( () => require( '../action-departures-live' ) );
	}

	beforeEach( () => {
		jest.useFakeTimers();
		hidden = false;
		reducedMotion = false;
		Object.defineProperty( document, 'hidden', { configurable: true, get: () => hidden } );
		window.matchMedia = jest.fn( () => ( { matches: reducedMotion } ) );
		animate = jest.fn( () => ( { finished: Promise.resolve() } ) );
		Element.prototype.animate = animate;
		global.fetch = jest.fn().mockResolvedValue( response( row( 1, 'Scheduled' ), '10:05' ) );
	} );

	afterEach( () => {
		window.dispatchEvent( new Event( 'pagehide' ) );
		document.body.innerHTML = '';
		jest.clearAllTimers();
		jest.useRealTimers();
		global.fetch = originalFetch;
		window.matchMedia = originalMatchMedia;
		Element.prototype.animate = originalAnimate;
		if ( originalHidden ) {
			Object.defineProperty( document, 'hidden', originalHidden );
		} else {
			delete document.hidden;
		}
	} );

	test( 'polls after five minutes and flips the whole board after every successful refresh', async () => {
		start( html( row( 1, 'Scheduled' ) + row( 2, 'Scheduled' ) + row( 3, 'Scheduled' ) ) );
		const unchanged = document.querySelector( '[data-action-id="1"]' );
		const link = unchanged.querySelector( 'a' );
		link.focus();
		global.fetch.mockResolvedValue( response( row( 1, 'Scheduled' ) + row( 2, 'Awaiting run' ) + row( 4, 'Scheduled' ), '10:05' ) );
		await jest.advanceTimersByTimeAsync( 299999 );
		expect( global.fetch ).not.toHaveBeenCalled();
		await jest.advanceTimersByTimeAsync( 1 );
		expect( document.querySelector( '[data-action-id="1"]' ) ).toBe( unchanged );
		expect( document.activeElement ).toBe( link );
		expect( document.querySelector( '[data-action-id="2"]' ).textContent ).toContain( 'Awaiting run' );
		expect( document.querySelector( '[data-action-id="3"]' ) ).toBeNull();
		expect( document.querySelector( '[data-action-id="4"]' ) ).not.toBeNull();
		expect( animate.mock.instances ).toContain( unchanged.querySelector( 'span' ) );
		expect( animate.mock.instances ).toContain( document.querySelector( 'h1 span' ) );
		expect( animate ).toHaveBeenCalledTimes( 5 );
		expect( document.querySelector( '.wc-departures__snapshot' ).textContent ).toBe( '10:05' );
		animate.mockClear();
		await jest.advanceTimersByTimeAsync( 300000 );
		expect( animate ).toHaveBeenCalledTimes( 4 );
	} );

	test( 'pauses when hidden and refreshes once when an overdue tab becomes visible', async () => {
		start();
		hidden = true;
		document.dispatchEvent( new Event( 'visibilitychange' ) );
		await jest.advanceTimersByTimeAsync( 900000 );
		expect( global.fetch ).not.toHaveBeenCalled();
		hidden = false;
		document.dispatchEvent( new Event( 'visibilitychange' ) );
		await jest.advanceTimersByTimeAsync( 1 );
		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'keeps the last board on failure and recovers without animations when motion is reduced', async () => {
		start();
		const before = document.querySelector( 'tbody' ).innerHTML;
		global.fetch.mockRejectedValueOnce( new Error( 'Offline' ) );
		await jest.advanceTimersByTimeAsync( 300000 );
		expect( document.querySelector( 'tbody' ).innerHTML ).toBe( before );
		expect( document.querySelector( '.wc-departures__snapshot' ).textContent ).toBe( '10:00' );
		expect( document.querySelector( '[role="status"], .wc-departures__live-message' ).textContent ).toBe( 'Failed' );
		reducedMotion = true;
		global.fetch.mockResolvedValueOnce( response( '', '10:10' ) );
		await jest.advanceTimersByTimeAsync( 300000 );
		expect( document.querySelector( 'tbody' ).rows ).toHaveLength( 0 );
		expect( document.querySelector( '.wc-departures__empty' ).hidden ).toBe( false );
		expect( animate ).not.toHaveBeenCalled();
	} );

	test( 'does not overlap a pending request with a manual refresh or run on the snapshot', async () => {
		let finish;
		global.fetch.mockImplementationOnce( () => new Promise( resolve => { finish = resolve; } ) );
		start();
		await jest.advanceTimersByTimeAsync( 300000 );
		document.querySelector( '.wc-departures__refresh' ).click();
		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		finish( response( row( 1, 'Scheduled' ), '10:05' ) );
		await jest.advanceTimersByTimeAsync( 1 );
		window.dispatchEvent( new Event( 'pagehide' ) );
		start( '<div class="wc-departures">Snapshot</div>' );
		global.fetch.mockClear();
		await jest.advanceTimersByTimeAsync( 600000 );
		expect( global.fetch ).not.toHaveBeenCalled();
	} );
} );
