import { vi } from 'vitest';
vi.mock( '@wordpress/i18n', () => {
	const mock = {
		__: ( str: string ) => str,
		sprintf: ( format: string, value: string ) =>
			format.replace( '%s', value ),
		isRTL: vi.fn( () => false ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
