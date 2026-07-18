/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useLaunchYourStore } from '../use-launch-your-store';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	optionsStore: { name: 'wc/admin/options' },
} ) );

describe( 'useLaunchYourStore', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'indicates when option fetching is disabled without selecting the store', () => {
		const select = jest.fn();
		( useSelect as jest.Mock ).mockImplementation( ( mapSelect ) =>
			mapSelect( select )
		);

		const { result } = renderHook( () =>
			useLaunchYourStore( { enabled: false } )
		);

		expect( result.current ).toMatchObject( {
			isLoading: false,
			comingSoon: null,
			storePagesOnly: null,
			privateLink: null,
			shareKey: null,
			launchYourStoreEnabled: null,
			isFetchingDisabled: true,
		} );
		expect( select ).not.toHaveBeenCalled();
	} );

	it( 'indicates when option fetching is enabled and preserves option values', () => {
		const options: Record< string, string > = {
			woocommerce_coming_soon: 'yes',
			woocommerce_store_pages_only: 'no',
			woocommerce_private_link: 'yes',
			woocommerce_share_key: 'evergreen-share-key',
		};
		const hasFinishedResolution = jest.fn().mockReturnValue( true );
		const getOption = jest.fn( ( option: string ) => options[ option ] );
		const select = jest.fn().mockReturnValue( {
			hasFinishedResolution,
			getOption,
		} );
		( useSelect as jest.Mock ).mockImplementation( ( mapSelect ) =>
			mapSelect( select )
		);

		const { result } = renderHook( () => useLaunchYourStore() );

		expect( result.current ).toMatchObject( {
			isLoading: false,
			comingSoon: 'yes',
			storePagesOnly: 'no',
			privateLink: 'yes',
			shareKey: 'evergreen-share-key',
			launchYourStoreEnabled: true,
			isFetchingDisabled: false,
		} );
		expect( getOption.mock.calls.map( ( [ option ] ) => option ) ).toEqual(
			Object.keys( options )
		);
	} );
} );
