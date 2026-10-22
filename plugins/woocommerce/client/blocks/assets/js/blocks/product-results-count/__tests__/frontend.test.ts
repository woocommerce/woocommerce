const mockGetServerContext = jest.fn();
const mockGetElement = jest.fn();
const mockSpeak = jest.fn();
let mockCallbacks: {
	announceResultsCount: () => ( () => void ) | void;
} | null = null;
let announcementDelayMs: number;
// Cleanups returned by each block's last watch run, as the watch effect keeps them.
let cleanups = new Map< HTMLElement, () => void >();

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getElement: mockGetElement,
		getServerContext: mockGetServerContext,
		store: jest.fn( ( _name, definition ) => {
			mockCallbacks = definition.callbacks;
			return definition;
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/a11y', () => ( { speak: mockSpeak } ), {
	virtual: true,
} );

// Blocks the server rendered with the page; tests add them before the module loads.
let firstBlock: HTMLElement;
let secondBlock: HTMLElement;

const pageBlock = () => {
	const block = document.createElement( 'div' );
	block.className = 'wc-block-product-results-count';
	document.body.append( block );
	return block;
};

const announce = ( text: string, block: HTMLElement = firstBlock ) => {
	if ( ! mockCallbacks ) {
		throw new Error( 'Product Results Count store was not registered.' );
	}
	// Like the watch effect, run the previous cleanup before re-running the callback.
	cleanups.get( block )?.();
	mockGetElement.mockReturnValue( { ref: block } );
	mockGetServerContext.mockReturnValue( { announcement: text } );
	const cleanup = mockCallbacks.announceResultsCount();
	if ( cleanup ) {
		cleanups.set( block, cleanup );
	}
};

// The router removed the block, for example on a page with no results.
const removeBlock = ( block: HTMLElement = firstBlock ) => {
	cleanups.get( block )?.();
	cleanups.delete( block );
};

const settle = async () => {
	jest.runAllTimers();
	// Let the dynamic import of @wordpress/a11y resolve.
	await new Promise( jest.requireActual( 'timers' ).setImmediate );
};

describe( 'Product Results Count announcement', () => {
	beforeEach( async () => {
		jest.useFakeTimers();
		jest.resetModules();
		mockGetServerContext.mockReset();
		mockGetElement.mockReset();
		mockSpeak.mockReset();
		mockCallbacks = null;
		cleanups = new Map();
		document.body.innerHTML = '';
		firstBlock = pageBlock();
		secondBlock = pageBlock();
		await jest.isolateModulesAsync( async () => {
			( { ANNOUNCEMENT_DELAY_MS: announcementDelayMs } =
				await import( '../frontend' ) );
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'does not announce on the initial render', async () => {
		announce( 'Showing 1–16 of 28 results' );
		await settle();

		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'does not announce on the initial render of several blocks', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 1–16 of 28 results', secondBlock );
		await settle();

		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'announces the new count after a navigation', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 17–28 of 28 results' );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );

	it( 'announces once, with the last count, after quick successive changes', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing all 9 results' );
		announce( 'Showing all 3 results' );
		announce( 'Showing all 2 results' );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing all 2 results',
			'polite'
		);
	} );

	it( 'waits before announcing so the router speaks first', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing all 2 results' );
		jest.advanceTimersByTime( announcementDelayMs - 1 );
		await new Promise( jest.requireActual( 'timers' ).setImmediate );

		expect( mockSpeak ).not.toHaveBeenCalled();

		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'announces an unchanged count after a navigation', async () => {
		announce( 'Showing all 5 results' );
		announce( 'Showing all 5 results' );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing all 5 results',
			'polite'
		);
	} );

	it( 'announces once when two blocks show the same new count', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 1–16 of 28 results', secondBlock );

		announce( 'Showing 17–28 of 28 results' );
		announce( 'Showing 17–28 of 28 results', secondBlock );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );

	it( 'announces the changed count when a later block is unchanged', async () => {
		const otherQueryBlock = secondBlock;
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 1–4 of 12 results', otherQueryBlock );

		announce( 'Showing 17–28 of 28 results' );
		announce( 'Showing 1–4 of 12 results', otherQueryBlock );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );

	it( 'announces the changed count when an earlier block is unchanged', async () => {
		const otherQueryBlock = secondBlock;
		announce( 'Showing 1–4 of 12 results', otherQueryBlock );
		announce( 'Showing 1–16 of 28 results' );

		announce( 'Showing 1–4 of 12 results', otherQueryBlock );
		announce( 'Showing 17–28 of 28 results' );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );

	it( 'does not cancel a pending announcement when another block mounts', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 17–28 of 28 results' );
		announce(
			'Showing 17–28 of 28 results',
			document.createElement( 'div' )
		);
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );

	it( 'announces an unchanged count on the navigation after a changed one', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing all 5 results' );
		await settle();
		announce( 'Showing all 5 results' );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 2 );
		expect( mockSpeak ).toHaveBeenLastCalledWith(
			'Showing all 5 results',
			'polite'
		);
	} );

	describe( 'on a page loaded with a query string', () => {
		beforeEach( () => {
			window.history.pushState( {}, '', '/shop/?orderby=price' );
		} );

		afterEach( () => {
			window.history.pushState( {}, '', '/' );
		} );

		it( 'announces the count once on page load', async () => {
			announce(
				'Showing 1–16 of 28 results Sorted by price: low to high'
			);
			announce(
				'Showing 1–16 of 28 results Sorted by price: low to high',
				secondBlock
			);
			await settle();

			expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
			expect( mockSpeak ).toHaveBeenCalledWith(
				'Showing 1–16 of 28 results Sorted by price: low to high',
				'polite'
			);
		} );

		it( 'does not let a mounting block replace a changed count', async () => {
			announce( 'Showing 1–16 of 28 results' );
			await settle();
			mockSpeak.mockReset();

			announce( 'Showing 17–28 of 28 results' );
			announce(
				'Showing 1–4 of 12 results',
				document.createElement( 'div' )
			);
			await settle();

			expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
			expect( mockSpeak ).toHaveBeenCalledWith(
				'Showing 17–28 of 28 results',
				'polite'
			);
		} );
	} );

	it( 'does not announce an empty count', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( '' );
		await settle();

		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'drops a pending count when the same block then shows no results', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing the single result' );
		jest.advanceTimersByTime( 300 );
		announce( '' );
		await settle();

		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'announces a block that mounts after a navigation, such as clearing filters with no results', async () => {
		announce( 'Showing 1–16 of 28 results' );
		removeBlock();
		await settle();

		announce(
			'Showing 1–16 of 28 results',
			document.createElement( 'div' )
		);
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 1–16 of 28 results',
			'polite'
		);
	} );

	it( 'drops a pending count when the block is removed from the page', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing the single result' );
		jest.advanceTimersByTime( 300 );
		removeBlock();
		await settle();

		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( "keeps another block's pending count when a block is removed", async () => {
		const otherQueryBlock = secondBlock;
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 1–4 of 12 results', otherQueryBlock );

		announce( 'Showing 17–28 of 28 results' );
		removeBlock( otherQueryBlock );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );

	it( "keeps another block's pending count when a block shows no results", async () => {
		const otherQueryBlock = secondBlock;
		announce( 'Showing 1–16 of 28 results' );
		announce( 'Showing 1–4 of 12 results', otherQueryBlock );

		announce( 'Showing 17–28 of 28 results' );
		announce( '', otherQueryBlock );
		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Showing 17–28 of 28 results',
			'polite'
		);
	} );
} );
