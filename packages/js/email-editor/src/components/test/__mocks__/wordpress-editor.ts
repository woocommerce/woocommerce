jest.mock( '@wordpress/editor', () => ( {
	useEntitiesSavedStatesIsDirty: jest.fn(),
	store: {},
	privateApis: {
		// Mock the private APIs that are used by the email editor
		Editor: jest.fn( () => null ),
		FullscreenMode: jest.fn( () => null ),
		ViewMoreMenuGroup: jest.fn( () => null ),
		BackButton: jest.fn( () => null ),
	},
} ) );
