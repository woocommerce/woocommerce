/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';
import { useSettings, useUserPreferences } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ScheduledUpdatesPromotionNotice from '../index';

// Mock dependencies
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => {
	const originalModule = jest.requireActual( '@woocommerce/data' );
	return {
		...originalModule,
		useSettings: jest.fn(),
		useUserPreferences: jest.fn(),
	};
} );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn( ( path: string ) => `http://example.com/${ path }` ),
} ) );

const mockUseSettings = useSettings as jest.MockedFunction<
	typeof useSettings
>;
const mockUseUserPreferences = useUserPreferences as jest.MockedFunction<
	typeof useUserPreferences
>;
const mockRecordEvent = recordEvent as jest.MockedFunction<
	typeof recordEvent
>;
const mockGetAdminLink = getAdminLink as jest.MockedFunction<
	typeof getAdminLink
>;

describe( 'ScheduledUpdatesPromotionNotice', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		// Set up default feature flag
		( window as any ).wcAdminFeatures = {
			'analytics-scheduled-import': true,
		};

		// Set up default mocks
		mockUseSettings.mockReturnValue( {
			wcAdminSettings: {},
		} as unknown as ReturnType< typeof useSettings > );

		mockUseUserPreferences.mockReturnValue( {
			updateUserPreferences: jest.fn(),
			scheduled_updates_promotion_notice_dismissed: undefined,
			isRequesting: false,
		} as unknown as ReturnType< typeof useUserPreferences > );

		mockGetAdminLink.mockReturnValue(
			'http://example.com/admin.php?page=wc-admin&path=/analytics/settings'
		);
	} );

	describe( 'Feature flag check', () => {
		test( 'should not render when feature flag is disabled', () => {
			( window as any ).wcAdminFeatures = {
				'analytics-scheduled-import': false,
			};

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should not render when feature flag is undefined', () => {
			( window as any ).wcAdminFeatures = {};

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should render when feature flag is enabled', () => {
			( window as any ).wcAdminFeatures = {
				'analytics-scheduled-import': true,
			};

			render( <ScheduledUpdatesPromotionNotice /> );

			expect(
				screen.getByText( ( content, element ) => {
					const textContent = element?.textContent || '';
					return (
						element?.tagName.toLowerCase() === 'p' &&
						textContent.includes(
							'Analytics now supports scheduled updates, providing improved performance. Enable it in'
						) &&
						textContent.includes( 'Settings' )
					);
				} )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Option value check', () => {
		test( 'should not render when option is set to "no"', () => {
			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {
					woocommerce_analytics_scheduled_import: 'no',
				},
			} as unknown as ReturnType< typeof useSettings > );

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should not render when option is set to "yes"', () => {
			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {
					woocommerce_analytics_scheduled_import: 'yes',
				},
			} as unknown as ReturnType< typeof useSettings > );

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should render when option is undefined', () => {
			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {},
			} as unknown as ReturnType< typeof useSettings > );

			render( <ScheduledUpdatesPromotionNotice /> );

			expect(
				screen.getByText( ( content, element ) => {
					const textContent = element?.textContent || '';
					return (
						element?.tagName.toLowerCase() === 'p' &&
						textContent.includes(
							'Analytics now supports scheduled updates, providing improved performance. Enable it in'
						) &&
						textContent.includes( 'Settings' )
					);
				} )
			).toBeInTheDocument();
		} );

		test( 'should render when option is null', () => {
			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {
					woocommerce_analytics_scheduled_import: null,
				},
			} as unknown as ReturnType< typeof useSettings > );

			render( <ScheduledUpdatesPromotionNotice /> );

			expect(
				screen.getByText( ( content, element ) => {
					const textContent = element?.textContent || '';
					return (
						element?.tagName.toLowerCase() === 'p' &&
						textContent.includes(
							'Analytics now supports scheduled updates, providing improved performance. Enable it in'
						) &&
						textContent.includes( 'Settings' )
					);
				} )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Dismissal check', () => {
		test( 'should not render when notice is dismissed', () => {
			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: 'yes',
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should render when notice is not dismissed', () => {
			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: undefined,
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			render( <ScheduledUpdatesPromotionNotice /> );

			expect(
				screen.getByText( ( content, element ) => {
					const textContent = element?.textContent || '';
					return (
						element?.tagName.toLowerCase() === 'p' &&
						textContent.includes(
							'Analytics now supports scheduled updates, providing improved performance. Enable it in'
						) &&
						textContent.includes( 'Settings' )
					);
				} )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Dismissal functionality', () => {
		test( 'should call updateUserPreferences when dismiss button is clicked', async () => {
			const mockUpdateUserPreferences = jest.fn();
			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: mockUpdateUserPreferences,
				scheduled_updates_promotion_notice_dismissed: undefined,
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			render( <ScheduledUpdatesPromotionNotice /> );

			const dismissButton = screen.getByLabelText(
				'Dismiss this notice.'
			);
			await userEvent.click( dismissButton );

			expect( mockUpdateUserPreferences ).toHaveBeenCalledWith( {
				scheduled_updates_promotion_notice_dismissed: 'yes',
			} );
		} );

		test( 'should fire tracking event when dismiss button is clicked', async () => {
			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: undefined,
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			render( <ScheduledUpdatesPromotionNotice /> );

			const dismissButton = screen.getByLabelText(
				'Dismiss this notice.'
			);
			await userEvent.click( dismissButton );

			expect( mockRecordEvent ).toHaveBeenCalledWith(
				'scheduled_updates_promotion_notice_dismissed'
			);
		} );
	} );

	describe( 'Link generation', () => {
		test( 'should generate correct settings link', () => {
			render( <ScheduledUpdatesPromotionNotice /> );

			const settingsLink = screen.getByLabelText( 'Analytics settings' );

			expect( settingsLink ).toBeInTheDocument();
			expect( settingsLink ).toHaveAttribute(
				'href',
				'http://example.com/admin.php?page=wc-admin&path=/analytics/settings'
			);
		} );

		test( 'should call getAdminLink with correct path', () => {
			render( <ScheduledUpdatesPromotionNotice /> );

			expect( mockGetAdminLink ).toHaveBeenCalledWith(
				'admin.php?page=wc-admin&path=/analytics/settings'
			);
		} );
	} );

	describe( 'Notice content', () => {
		test( 'should display correct notice message', () => {
			render( <ScheduledUpdatesPromotionNotice /> );

			expect(
				screen.getByText( ( content, element ) => {
					const textContent = element?.textContent || '';
					return (
						element?.tagName.toLowerCase() === 'p' &&
						textContent.includes(
							'Analytics now supports scheduled updates, providing improved performance. Enable it in'
						) &&
						textContent.includes( 'Settings' )
					);
				} )
			).toBeInTheDocument();
		} );

		test( 'should have correct CSS classes', () => {
			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			const notice = container.querySelector(
				'.notice.notice-info.is-dismissible'
			);
			expect( notice ).toBeInTheDocument();
		} );

		test( 'should render dismiss button with correct attributes', () => {
			render( <ScheduledUpdatesPromotionNotice /> );

			const dismissButton = screen.getByLabelText(
				'Dismiss this notice.'
			);

			expect( dismissButton ).toBeInTheDocument();
			expect( dismissButton ).toHaveClass(
				'woocommerce-message-close',
				'notice-dismiss'
			);
		} );
	} );

	describe( 'Combined conditions', () => {
		test( 'should render when all conditions are met', () => {
			( window as any ).wcAdminFeatures = {
				'analytics-scheduled-import': true,
			};

			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {},
			} as unknown as ReturnType< typeof useSettings > );

			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: undefined,
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			render( <ScheduledUpdatesPromotionNotice /> );

			expect(
				screen.getByText( ( content, element ) => {
					const textContent = element?.textContent || '';
					return (
						element?.tagName.toLowerCase() === 'p' &&
						textContent.includes(
							'Analytics now supports scheduled updates, providing improved performance. Enable it in'
						) &&
						textContent.includes( 'Settings' )
					);
				} )
			).toBeInTheDocument();
		} );

		test( 'should not render when feature flag is disabled even if other conditions are met', () => {
			( window as any ).wcAdminFeatures = {
				'analytics-scheduled-import': false,
			};

			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {},
			} as unknown as ReturnType< typeof useSettings > );

			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: undefined,
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should not render when option is set even if feature flag is enabled', () => {
			( window as any ).wcAdminFeatures = {
				'analytics-scheduled-import': true,
			};

			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {
					woocommerce_analytics_scheduled_import: 'no',
				},
			} as unknown as ReturnType< typeof useSettings > );

			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: undefined,
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );

		test( 'should not render when dismissed even if feature flag is enabled and option is not set', () => {
			( window as any ).wcAdminFeatures = {
				'analytics-scheduled-import': true,
			};

			mockUseSettings.mockReturnValue( {
				wcAdminSettings: {},
			} as unknown as ReturnType< typeof useSettings > );

			mockUseUserPreferences.mockReturnValue( {
				updateUserPreferences: jest.fn(),
				scheduled_updates_promotion_notice_dismissed: 'yes',
				isRequesting: false,
			} as unknown as ReturnType< typeof useUserPreferences > );

			const { container } = render( <ScheduledUpdatesPromotionNotice /> );

			expect( container.firstChild ).toBeNull();
		} );
	} );
} );
