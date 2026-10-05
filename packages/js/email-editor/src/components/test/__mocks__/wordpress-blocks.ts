import { vi } from 'vitest';
vi.mock( '@wordpress/blocks', () => {
	const mock = {
		serialize: vi.fn(),
		parse: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
