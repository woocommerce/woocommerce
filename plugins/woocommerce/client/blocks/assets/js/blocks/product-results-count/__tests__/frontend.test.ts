const mockGetServerContext = jest.fn();
const mockSpeak = jest.fn();
let mockCallbacks: { announceResultsCount: () => void } | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
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

const announce = ( text: string ) => {
	if ( ! mockCallbacks ) {
		throw new Error( 'Product Results Count store was not registered.' );
	}
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
		mockSpeak.mockReset();
		mockCallbacks = null;
		await jest.isolateModulesAsync( async () => {
			await import( '../frontend' );
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
		jest.advanceTimersByTime( 499 );
		await new Promise( jest.requireActual( 'timers' ).setImmediate );

		expect( mockSpeak ).not.toHaveBeenCalled();

		await settle();

		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not announce an empty count', async () => {
		announce( 'Showing 1–16 of 28 results' );
		announce( '' );
		await settle();

		expect( mockSpeak ).not.toHaveBeenCalled();
	} );
} );
