jest.mock( '@wordpress/block-editor', () => ( {
	store: {},
	privateApis: {
		// Mock the private APIs that are used by the email editor
		ColorPanel: jest.fn( () => null ),
		useGlobalStylesOutputWithConfig: jest.fn( () => [ [], {} ] ),
	},
} ) );
