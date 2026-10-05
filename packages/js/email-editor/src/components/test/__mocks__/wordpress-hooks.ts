import { vi } from 'vitest';
vi.mock( '@wordpress/hooks', () => {
	const mock = {
		applyFilters: vi.fn( ( _hook: string, value: unknown ) => value ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
