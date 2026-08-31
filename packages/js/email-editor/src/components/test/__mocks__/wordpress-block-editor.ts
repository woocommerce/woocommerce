jest.mock( '@wordpress/block-editor', () => ( {
	store: {},
	privateApis: {
		// Mock the private APIs that are used by the email editor
		ColorPanel: jest.fn( () => null ),
		BackgroundPanel: jest.fn( () => null ),
		useHasColorPanel: jest.fn( () => true ),
		useHasBackgroundPanel: jest.fn( () => false ),
		useGlobalStylesOutputWithConfig: jest.fn( () => [ [], {} ] ),
	},
} ) );
