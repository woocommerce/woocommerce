type MockOverrides = Record< string, unknown >;

export const getActualWordPressData = () => {
	return jest.requireActual( '@wordpress/data' );
};

const defineValue = (
	target: Record< string, unknown >,
	key: string,
	value: unknown
) => {
	Object.defineProperty( target, key, {
		configurable: true,
		enumerable: true,
		value,
		writable: true,
	} );
};

/**
 * Creates a partial mock for @wordpress/data while keeping the real module
 * exports available. Avoid object spreading here: newer Gutenberg packages use
 * getter-based CommonJS exports, and spreading eagerly evaluates those getters
 * while Jest is still initializing the mock.
 */
export const mockWordPressData = ( overrides: MockOverrides = {} ) => {
	const wpData = getActualWordPressData();
	const mock = {};
	const overrideKeys = new Set( Object.keys( overrides ) );
	const descriptors = Object.fromEntries(
		Object.entries( Object.getOwnPropertyDescriptors( wpData ) ).filter(
			( [ key ] ) => key !== '__esModule' && ! overrideKeys.has( key )
		)
	);

	Object.defineProperties( mock, descriptors );
	defineValue( mock, '__esModule', true );

	for ( const [ key, value ] of Object.entries( overrides ) ) {
		defineValue( mock, key, value );
	}

	return mock;
};
