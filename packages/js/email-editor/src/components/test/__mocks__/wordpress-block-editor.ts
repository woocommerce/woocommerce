import { vi } from 'vitest';
vi.mock( '@wordpress/block-editor', () => {
	const mock = {
		store: {},
		privateApis: {
			// Mock the private APIs that are used by the email editor
			ColorPanel: vi.fn( () => null ),
			BackgroundPanel: vi.fn( () => null ),
			useHasColorPanel: vi.fn( () => true ),
			useHasBackgroundPanel: vi.fn( () => false ),
			useGlobalStylesOutputWithConfig: vi.fn( () => [ [], {} ] ),
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
