/**
 * External dependencies
 */
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { AnyInterpreter } from 'xstate';
/**
 * Internal dependencies
 */
import { Intro } from '../';
import { FlowType } from '~/customize-store/types';

jest.mock( '../../assembler-hub/site-hub', () => ( {
	SiteHub: jest.fn( () => null ),
} ) );
jest.mock( '~/utils/react-hooks/use-network-status', () => ( {
	useNetworkStatus: jest.fn(),
} ) );
describe( 'Intro Modals', () => {
	it( 'should display DesignChangeWarningModal when activeThemeHasMods and button is clicked', async () => {
		const sendEventMock = jest.fn();
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
						activeThemeHasMods: true,
						customizeStoreTaskCompleted: false,
						currentThemeIsAiGenerated: false,
					},
					themeConfiguration: {},
					transitionalScreen: {
						hasCompleteSurvey: false,
					},
					flowType: FlowType.AIOnline,
					isFontLibraryAvailable: false,
				} }
				currentState={ 'intro' }
				parentMachine={ null as unknown as AnyInterpreter }
			/>
		);

		const bannerButton = screen.getByRole( 'button', {
			name: /Design with AI/i,
		} );
		fireEvent.click( bannerButton );

		await waitFor( () => {
			expect(
				screen.getByText(
					/Are you sure you want to start a new design?/i
				)
			).toBeInTheDocument();
		} );

		const modalButton = screen.getByRole( 'button', {
			name: /Design with AI/i,
		} );
		fireEvent.click( modalButton );

		expect( sendEventMock ).toHaveBeenCalledWith( {
			type: 'DESIGN_WITH_AI',
		} );
	} );

	it( 'should display StartOverWarningModal when customizeStoreTaskCompleted and currentThemeIsAiGenerated and button is clicked', async () => {
		const sendEventMock = jest.fn();
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
						activeThemeHasMods: false,
						customizeStoreTaskCompleted: true,
						currentThemeIsAiGenerated: true,
					},
					themeConfiguration: {},
					transitionalScreen: {
						hasCompleteSurvey: false,
					},
					flowType: FlowType.AIOnline,
					isFontLibraryAvailable: false,
				} }
				currentState={ 'intro' }
				parentMachine={ null as unknown as AnyInterpreter }
			/>
		);

		const bannerButton = screen.getByRole( 'button', {
			name: /Create a new one/i,
		} );
		fireEvent.click( bannerButton );

		await waitFor( () => {
			expect(
				screen.getByText( /Are you sure you want to start over?/i )
			).toBeInTheDocument();
		} );

		const modalButton = screen.getByRole( 'button', {
			name: /Start again/i,
		} );
		fireEvent.click( modalButton );

		expect( sendEventMock ).toHaveBeenCalledWith( {
			type: 'DESIGN_WITH_AI',
		} );
	} );

	it( 'should display StartNewDesignWarningModal when customizeStoreTaskCompleted and not currentThemeIsAiGenerated and button is clicked', async () => {
		const sendEventMock = jest.fn();
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
						activeThemeHasMods: false,
						customizeStoreTaskCompleted: true,
						currentThemeIsAiGenerated: false,
					},
					themeConfiguration: {},
					transitionalScreen: {
						hasCompleteSurvey: false,
					},
					flowType: FlowType.AIOnline,
					isFontLibraryAvailable: false,
				} }
				currentState={ 'intro' }
				parentMachine={ null as unknown as AnyInterpreter }
			/>
		);

		const bannerButton = screen.getByRole( 'button', {
			name: /Design with AI/i,
		} );
		fireEvent.click( bannerButton );

		await waitFor( () => {
			expect(
				screen.getByText(
					/Are you sure you want to start a new design?/i
				)
			).toBeInTheDocument();
		} );

		const modalButton = screen.getByRole( 'button', {
			name: /Design with AI/i,
		} );
		fireEvent.click( modalButton );

		expect( sendEventMock ).toHaveBeenCalledWith( {
			type: 'DESIGN_WITH_AI',
		} );
	} );
} );
