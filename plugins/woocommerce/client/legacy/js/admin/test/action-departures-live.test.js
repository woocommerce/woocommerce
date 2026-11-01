describe( 'Live departures board', () => {
	let hidden;
	let reducedMotion;
	let animate;
	const originalFetch = global.fetch;
	const originalHidden = Object.getOwnPropertyDescriptor( document, 'hidden' );
	const originalMatchMedia = window.matchMedia;
	const originalAnimate = Element.prototype.animate;

	function tile( character ) {
		return `<span class="wc-departures__flap" data-character="${ character }" data-before=" ">` +
			`<span class="wc-departures__value">${ character }</span>` +
			'<span class="wc-departures__leaf wc-departures__leaf--top"> </span>' +
			`<span class="wc-departures__leaf wc-departures__leaf--bottom">${ character }</span></span>`;
	}

	function row( id, label ) {
		return `<tr data-action-id="${ id }"><td><a href="#action-${ id }">Action ${ id }</a></td>` +
			`<td>${ tile( label ) }</td></tr>`;
	}

	function html( rows, stamp = '10:00' ) {
		return `<div class="wc-departures" data-refresh-url="/wp-admin/admin-ajax.php?nonce=fresh"
			data-message-refreshing="Updating" data-message-updated="Updated" data-message-failed="Failed">
			<h1>${ tile( 'D' ) }</h1>
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

	test( 'polls after five minutes and flips each character half after every successful refresh', async () => {
		start( html( row( 1, 'Scheduled' ) + row( 2, 'Scheduled' ) + row( 3, 'Scheduled' ) ) );
		const unchanged = document.querySelector( '[data-action-id="1"]' );
		const unchangedTile = unchanged.querySelector( '.wc-departures__flap' );
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
		expect( animate.mock.instances ).toContain( unchangedTile.querySelector( '.wc-departures__leaf--top' ) );
		expect( animate.mock.instances ).toContain( unchangedTile.querySelector( '.wc-departures__leaf--bottom' ) );
		expect( animate.mock.instances ).toContain( document.querySelector( 'h1 .wc-departures__leaf--top' ) );
		expect( animate.mock.instances.every( element => element.classList.contains( 'wc-departures__leaf' ) ) ).toBe( true );
		document.querySelectorAll( '.wc-departures__leaf' ).forEach( leaf => expect( animate.mock.instances ).toContain( leaf ) );
		expect( document.querySelector( '.wc-departures__snapshot' ).textContent ).toBe( '10:05' );
		animate.mockClear();
		await jest.advanceTimersByTimeAsync( 300000 );
		document.querySelectorAll( '.wc-departures__leaf' ).forEach( leaf => expect( animate.mock.instances ).toContain( leaf ) );
		expect( unchanged.querySelector( '.wc-departures__flap' ) ).toBe( unchangedTile );
	} );

	test( 'folds the old upper half before unfolding the new lower half around the center hinge', async () => {
		const finish = [];
		animate.mockImplementation( () => ( { finished: new Promise( resolve => finish.push( resolve ) ) } ) );
		start( html( row( 1, '1' ) ) );
		global.fetch.mockResolvedValue( response( row( 1, '2' ), '10:05' ) );
		document.querySelector( '.wc-departures__refresh' ).click();
		await jest.advanceTimersByTimeAsync( 1 );
		const character = document.querySelector( 'tbody .wc-departures__flap' );
		expect( character.dataset.before ).toBe( '1' );
		expect( character.dataset.character ).toBe( '2' );
		expect( character.querySelector( '.wc-departures__leaf--top' ).textContent ).toBe( '1' );
		expect( character.querySelector( '.wc-departures__leaf--bottom' ).textContent ).toBe( '2' );
		const top = animate.mock.calls[ animate.mock.instances.indexOf( character.querySelector( '.wc-departures__leaf--top' ) ) ];
		const bottom = animate.mock.calls[ animate.mock.instances.indexOf( character.querySelector( '.wc-departures__leaf--bottom' ) ) ];
		expect( top[ 0 ].map( frame => frame.transform ) ).toEqual( [ 'rotateX(0)', 'rotateX(-90deg)' ] );
		expect( bottom[ 0 ].map( frame => frame.transform ) ).toEqual( [ 'rotateX(90deg)', 'rotateX(0)' ] );
		expect( bottom[ 1 ].delay ).toBeGreaterThanOrEqual( top[ 1 ].delay + top[ 1 ].duration );
		expect( document.querySelector( 'tbody' ).getAttribute( 'aria-busy' ) ).toBe( 'true' );
		finish.forEach( resolve => resolve() );
		await jest.advanceTimersByTimeAsync( 1 );
		expect( character.dataset.before ).toBe( '2' );
		expect( document.querySelector( '.wc-departures__live-message' ).textContent ).toBe( 'Updated' );
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
