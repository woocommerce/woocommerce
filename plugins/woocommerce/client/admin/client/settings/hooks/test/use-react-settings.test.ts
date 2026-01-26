/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useReactSettings } from '../use-react-settings';

describe( 'useReactSettings', () => {
	const windowWithSettings = window as unknown as {
		wcSettings?: {
			admin?: Record< string, unknown >;
		};
	};
	const originalSettings = windowWithSettings.wcSettings;

	afterEach( () => {
		windowWithSettings.wcSettings = originalSettings;
	} );

	it( 'returns preloaded settings data', () => {
		const response = {
			id: 'general',
			title: 'General',
			description: '',
			values: {},
			groups: {},
		};

		windowWithSettings.wcSettings = {
			admin: {
				settings: {
					general: {
						default: response,
					},
				},
			},
		};

		const { result } = renderHook( () =>
			useReactSettings( {
				dataPath: [ 'settings', 'general', 'default' ],
			} )
		);

		expect( result.current.data ).toEqual( response );
		expect( result.current.isLoading ).toBe( false );
		expect( result.current.error ).toBeNull();
	} );

	it( 'sets an error when data is missing', async () => {
		windowWithSettings.wcSettings = {
			admin: {},
		};

		const { result } = renderHook( () =>
			useReactSettings( {
				dataPath: [ 'settings', 'general', 'default' ],
				missingDataMessage: 'Missing data',
			} )
		);

		await act( async () => {
			await Promise.resolve();
		} );

		expect( result.current.error?.message ).toBe( 'Missing data' );
		expect( result.current.isLoading ).toBe( false );
	} );

	it( 'refetches when settings data becomes available', async () => {
		windowWithSettings.wcSettings = {
			admin: {},
		};

		const { result } = renderHook( () =>
			useReactSettings( {
				dataPath: [ 'settings', 'general', 'default' ],
				missingDataMessage: 'Missing data',
			} )
		);

		await act( async () => {
			await Promise.resolve();
		} );

		const response = {
			id: 'general',
			title: 'General',
			description: '',
			values: {},
			groups: {},
		};

		windowWithSettings.wcSettings = {
			admin: {
				settings: {
					general: {
						default: response,
					},
				},
			},
		};

		act( () => {
			result.current.refetch();
		} );

		expect( result.current.data ).toEqual( response );
		expect( result.current.error ).toBeNull();
	} );
} );
