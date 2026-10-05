import { vi } from 'vitest';
vi.mock( '@wordpress/preferences', () => {
	const mock = {
		combineReducers: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
