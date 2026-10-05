import { vi } from 'vitest';
vi.mock( '@wordpress/editor', () => {
	const mock = {
		useEntitiesSavedStatesIsDirty: vi.fn(),
		store: {},
		privateApis: {
			// Mock the private APIs that are used by the email editor
			Editor: vi.fn( () => null ),
			FullscreenMode: vi.fn( () => null ),
			ViewMoreMenuGroup: vi.fn( () => null ),
			BackButton: vi.fn( () => null ),
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
