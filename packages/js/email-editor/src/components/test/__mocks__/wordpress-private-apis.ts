import { vi } from 'vitest';
vi.mock( '@wordpress/private-apis', () => {
	const mock = {
		__dangerousOptInToUnstableAPIsOnlyForCoreModules: vi.fn( () => ( {
			lock: vi.fn(),
			unlock: vi.fn( ( obj ) => {
				// Return the object itself if it has properties, or an empty object
				if ( obj && typeof obj === 'object' ) {
					return obj;
				}
				return {};
			} ),
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
