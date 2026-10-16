const mockGetServerContext = jest.fn();
const mockGetElement = jest.fn();
const mockSpeak = jest.fn();
let mockCallbacks: { announceResultsCount: () => void } | null = null;
let announcementDelayMs: number;

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

const firstBlock = document.createElement( 'div' );

const announce = ( text: string, block: HTMLElement = firstBlock ) => {
	if ( ! mockCallbacks ) {
		throw new Error( 'Product Results Count store was not registered.' );
	}
	mockGetElement.mockReturnValue( { ref: block } );
	mockGetServerContext.mockReturnValue( { announcement: text } );
	mockCallbacks.announceResultsCount();
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
		announce(
			'Showing 1–16 of 28 results',
			document.createElement( 'div' )
		);
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
		const secondBlock = document.createElement( 'div' );
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
		const otherQueryBlock = document.createElement( 'div' );
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
		const otherQueryBlock = document.createElement( 'div' );
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
				document.createElement( 'div' )
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
} );
