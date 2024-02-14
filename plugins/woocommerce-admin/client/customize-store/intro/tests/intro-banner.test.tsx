/**
 * External dependencies
 */
import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { AnyInterpreter } from 'xstate';

/**
 * Internal dependencies
 */
import { Intro } from '../';
import { useNetworkStatus } from '~/utils/react-hooks/use-network-status';
import { FlowType } from '~/customize-store/types';

jest.mock( '../../assembler-hub/site-hub', () => ( {
	SiteHub: jest.fn( () => null ),
} ) );
jest.mock( '~/utils/react-hooks/use-network-status', () => ( {
	useNetworkStatus: jest.fn(),
} ) );
describe( 'Intro Banners', () => {
	it( 'should display NetworkOfflineBanner when network is offline', () => {
		( useNetworkStatus as jest.Mock ).mockImplementation( () => true );
		render(
			<Intro
				sendEvent={ jest.fn() }
				context={ {
					intro: {
						hasErrors: false,
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
						currentThemeIsAiGenerated: false,
					},
					themeConfiguration: {},
					transitionalScreen: {
						hasCompleteSurvey: false,
					},
					flowType: FlowType.AIOnline,
					isFontLibraryAvailable: false,
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

	it( 'should display the default banner by default', () => {
		const sendEventMock = jest.fn();
		( useNetworkStatus as jest.Mock ).mockImplementation( () => false );
		render(
			<Intro
				sendEvent={ sendEventMock }
				context={ {
					intro: {
						hasErrors: false,
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
						currentThemeIsAiGenerated: false,
					},
					themeConfiguration: {},
					transitionalScreen: {
						hasCompleteSurvey: false,
					},
					flowType: FlowType.AIOnline,
					isFontLibraryAvailable: false,
					activeThemeHasMods: false,
				} }
				currentState={ 'intro' }
				parentMachine={ null as unknown as AnyInterpreter }
			/>
		);
		expect(
			screen.getByText( /Use the power of AI to design your store/i )
		).toBeInTheDocument();
		const button = screen.getByRole( 'button', {
			name: /Design with AI/i,
		} );
		fireEvent.click( button );
		expect( sendEventMock ).toHaveBeenCalledWith( {
			type: 'DESIGN_WITH_AI',
		} );
	} );

	it( 'should display the existing ai theme banner when customizeStoreTaskCompleted and currentThemeIsAiGenerated', () => {
		const sendEventMock = jest.fn();
		( useNetworkStatus as jest.Mock ).mockImplementation( () => false );
		render(
			<Intro
				sendEvent={ sendEventMock }
				context={ {
					intro: {
						hasErrors: false,
						activeTheme: '',
						themeData: {
							themes: [],
							_links: {
								browse_all: {
									href: '',
								},
							},
						},
						customizeStoreTaskCompleted: true,
						currentThemeIsAiGenerated: true,
					},
					themeConfiguration: {},
					transitionalScreen: {
						hasCompleteSurvey: false,
					},
					flowType: FlowType.AIOnline,
					isFontLibraryAvailable: false,
					activeThemeHasMods: false,
				} }
				currentState={ 'intro' }
				parentMachine={ null as unknown as AnyInterpreter }
			/>
		);
		expect(
			screen.getByText( /Customize your custom theme/i )
		).toBeInTheDocument();
	} );
} );
