jest.mock( '@wordpress/private-apis', () => ( {
	__dangerousOptInToUnstableAPIsOnlyForCoreModules: jest.fn( () => ( {
		unlock: jest.fn( ( obj ) => {
			// Return the object itself if it has properties, or an empty object
			if ( obj && typeof obj === 'object' ) {
				return obj;
			}
			return {};
		} ),
	} ) ),
} ) );
