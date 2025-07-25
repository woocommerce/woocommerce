/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { AnyInterpreter } from 'xstate';

/**
 * Internal dependencies
 */
import { Intro } from '../';
import { useNetworkStatus } from '~/utils/react-hooks/use-network-status';

jest.mock( '../../assembler-hub/site-hub', () => ( {
	SiteHub: jest.fn( () => null ),
} ) );
jest.mock( '~/utils/react-hooks/use-network-status', () => ( {
	useNetworkStatus: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		useSelect: jest.fn( () => ( {
			is_block_theme: true,
		} ) ),
	};
} );
describe( 'Intro Banners', () => {
	it( 'should display NetworkOfflineBanner when network is offline', () => {
		( useNetworkStatus as jest.Mock ).mockImplementation( () => true );
		render(
			<Intro
				sendEvent={ jest.fn() }
				context={ {
					intro: {
						hasErrors: false,
						errorStatus: undefined,
						activeTheme: '',
						themeData: {
							themes: [],
							_links: {
								browse_all: {
									href: '',
								},
							},
						},
						customizeStoreTaskCompleted: false,
					},
					themeConfiguration: {},
					isFontLibraryAvailable: false,
					isPTKPatternsAPIAvailable: false,
					activeThemeHasMods: false,
				} }
				currentState={ 'intro' }
				parentMachine={ null as unknown as AnyInterpreter }
			/>
		);

		expect(
			screen.getByText( /Please check your internet connection./i )
		).toBeInTheDocument();
	} );
} );
