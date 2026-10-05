import { vi } from 'vitest';
vi.mock( '@wordpress/data', () => {
	const mock = {
		select: vi.fn(),
		dispatch: vi.fn( () => ( {
			registerEntityAction: vi.fn(),
			unregisterEntityAction: vi.fn(),
		} ) ),
		use: vi.fn(),
		useDispatch: vi.fn(),
		useSelect: vi.fn(),
		createRegistrySelector: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
