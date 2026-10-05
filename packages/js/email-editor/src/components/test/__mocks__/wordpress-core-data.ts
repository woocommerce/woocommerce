import { vi } from 'vitest';
vi.mock( '@wordpress/core-data', () => {
	const mock = {
		createSelector: vi.fn(),
		store: {},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
