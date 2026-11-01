( function () {
	'use strict';

	const board = document.querySelector( '.wc-departures[data-refresh-url]' );
	if ( ! board ) {
		return;
	}

	const interval = 5 * 60 * 1000;
	const tbody = board.querySelector( 'tbody' );
	const message = board.querySelector( '.wc-departures__live-message' );
	const cellMarkup = new WeakMap();
	let refreshUrl = board.dataset.refreshUrl;
	let lastAttempt = Date.now();
	let timer;
	let controller;

	function schedule() {
		clearTimeout( timer );
		if ( board.isConnected && ! document.hidden ) {
			timer = setTimeout( refresh, Math.max( 0, interval - ( Date.now() - lastAttempt ) ) );
		}
	}

	function rememberCells( container ) {
		container.querySelectorAll( 'td' ).forEach( cell => cellMarkup.set( cell, cell.innerHTML ) );
	}

	function flipTiles( container, previous = new Map(), departing = false ) {
		const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		return Promise.all( Array.from( container.querySelectorAll( '.wc-departures__flap' ) ).map( function ( tile, index ) {
			const before = previous.has( tile ) ? previous.get( tile ) : ( departing ? tile.dataset.character : ' ' );
			const after = departing ? ' ' : tile.dataset.character;
			const top = tile.querySelector( '.wc-departures__leaf--top' );
			const bottom = tile.querySelector( '.wc-departures__leaf--bottom' );
			tile.dataset.before = before;
			tile.dataset.character = after;
			top.textContent = before;
			bottom.textContent = after;
			top.style.animation = 'none';
			bottom.style.animation = 'none';
			if ( reduced || ! top.animate ) {
				tile.dataset.before = after;
				return Promise.resolve();
			}
			const delay = ( index % 12 ) * 18;
			const fold = top.animate(
				[ { transform: 'rotateX(0)', opacity: 1 }, { transform: 'rotateX(-90deg)', opacity: 1 } ],
				{ duration: 260, delay, easing: 'ease-in', fill: 'backwards' }
			);
			const unfold = bottom.animate(
				[ { transform: 'rotateX(90deg)' }, { transform: 'rotateX(0)' } ],
				{ duration: 260, delay: delay + 260, easing: 'ease-out', fill: 'backwards' }
			);
			return Promise.all( [ fold.finished, unfold.finished ].map( finished => finished.catch( function () {} ) ) )
				.then( function () { tile.dataset.before = after; } );
		} ) );
	}

	async function update( next ) {
		const previous = new Map( Array.from( board.querySelectorAll( '.wc-departures__flap' ) )
			.map( tile => [ tile, tile.dataset.character ] ) );
		const nextRows = Array.from( next.querySelectorAll( 'tbody tr' ) );
		const ids = new Set( nextRows.map( row => row.dataset.actionId ) );
		const oldRows = new Map( Array.from( tbody.rows ).map( row => [ row.dataset.actionId, row ] ) );
		const removed = Array.from( tbody.rows ).filter( row => ! ids.has( row.dataset.actionId ) );
		await Promise.all( removed.map( row => flipTiles( row, previous, true ) ) );
		const focused = document.activeElement;
		const hadFocus = board.contains( focused );
		removed.forEach( row => row.remove() );

		nextRows.forEach( function ( nextRow, index ) {
			const row = oldRows.get( nextRow.dataset.actionId );
			if ( ! row ) {
				rememberCells( nextRow );
				tbody.insertBefore( nextRow, tbody.rows[ index ] || null );
				return;
			}
			Array.from( nextRow.cells ).forEach( function ( nextCell, cellIndex ) {
				const cell = row.cells[ cellIndex ];
				if ( cellMarkup.get( cell ) !== nextCell.innerHTML || cell.className !== nextCell.className ) {
					const before = Array.from( cell.querySelectorAll( '.wc-departures__flap' ) ).map( tile => tile.dataset.character );
					cellMarkup.set( cell, nextCell.innerHTML );
					cell.className = nextCell.className;
					cell.replaceChildren( ...Array.from( nextCell.childNodes ) );
					cell.querySelectorAll( '.wc-departures__flap' ).forEach( function ( tile, tileIndex ) {
						previous.set( tile, before[ tileIndex ] || ' ' );
					} );
				}
			} );
			if ( tbody.rows[ index ] !== row ) {
				tbody.insertBefore( row, tbody.rows[ index ] || null );
			}
		} );

		board.querySelector( '.wc-departures__empty' ).hidden = next.querySelector( '.wc-departures__empty' ).hidden;
		board.querySelector( '.wc-departures__snapshot' ).replaceChildren(
			...Array.from( next.querySelector( '.wc-departures__snapshot' ).childNodes )
		);
		const more = board.querySelector( '.wc-departures__footer a' );
		const nextMore = next.querySelector( '.wc-departures__footer a' );
		more.textContent = nextMore.textContent;
		more.href = nextMore.href;
		refreshUrl = next.dataset.refreshUrl;
		if ( hadFocus ) {
			const target = focused.isConnected ? focused : board.querySelector( '.wc-departures__refresh' );
			if ( document.activeElement !== target ) {
				target.focus( { preventScroll: true } );
			}
		}
		return previous;
	}

	async function refresh() {
		if ( controller || document.hidden || ! board.isConnected ) {
			return;
		}
		clearTimeout( timer );
		lastAttempt = Date.now();
		controller = new AbortController();
		const timeout = setTimeout( () => controller.abort(), 20000 );
		message.textContent = board.dataset.messageRefreshing;
		tbody.setAttribute( 'aria-busy', 'true' );
		try {
			const response = await fetch( refreshUrl, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal } );
			if ( ! response.ok ) {
				throw new Error( 'Refresh failed' );
			}
			const result = await response.json();
			if ( ! result.success || ! result.data || typeof result.data.html !== 'string' ) {
				throw new Error( 'Invalid board response' );
			}
			const next = new DOMParser().parseFromString( result.data.html, 'text/html' )
				.querySelector( '.wc-departures[data-refresh-url]' );
			if ( ! next || document.hidden ) {
				throw new Error( 'Board unavailable' );
			}
			const previous = await update( next );
			await flipTiles( board, previous );
			message.textContent = board.dataset.messageUpdated;
		} catch ( error ) {
			message.textContent = board.dataset.messageFailed;
		} finally {
			clearTimeout( timeout );
			controller = null;
			tbody.removeAttribute( 'aria-busy' );
			schedule();
		}
	}

	board.querySelector( '.wc-departures__refresh' ).addEventListener( 'click', function ( event ) {
		event.preventDefault();
		refresh();
	} );
	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden && controller ) {
			controller.abort();
		}
		schedule();
	} );
	window.addEventListener( 'pagehide', function () {
		clearTimeout( timer );
		if ( controller ) {
			controller.abort();
		}
	} );
	window.addEventListener( 'pageshow', schedule );
	rememberCells( tbody );
	schedule();
} )();
