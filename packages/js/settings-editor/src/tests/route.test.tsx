/**
 * External dependencies
 */
import { createElement, useContext } from '@wordpress/element';
import { screen, render, renderHook } from '@testing-library/react';
import { addAction, applyFilters, didFilter } from '@wordpress/hooks';
/* eslint-disable @woocommerce/dependency-group */
// @ts-ignore No types for this exist yet.
import { privateApis } from '@wordpress/router';
/* eslint-enable @woocommerce/dependency-group */

/**
 * Internal dependencies
 */
import { useActiveRoute, useModernRoutes } from '../route';

// Mock external dependencies
jest.mock( '@wordpress/hooks', () => ( {
	addAction: jest.fn(),
	removeAction: jest.fn(),
	applyFilters: jest.fn(),
	didFilter: jest.fn(),
} ) );

jest.mock( '@wordpress/router', () => ( {
	privateApis: {
		useLocation: jest.fn(),
	},
} ) );

jest.mock( '@wordpress/edit-site/build-module/lock-unlock', () => ( {
	unlock: jest.fn( ( apis ) => apis ),
} ) );

jest.mock( '../components/sidebar', () => ( {
	__esModule: true,
	Sidebar: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="sidebar-navigation-screen">{ children }</div>
	),
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useContext: jest.fn(),
} ) );

const mockSettingsPages = {
	pages: {
		general: {
			label: 'General',
			icon: 'settings',
			slug: 'general',
			sections: {
				default: {
					label: 'General',
					settings: [
						{
							title: 'Store Address',
							type: 'title' as const,
							desc: 'This is where your business is located.',
							id: 'store_address',
							value: false,
						},
					],
				},
			},
			is_modern: false,
			start: null,
			end: null,
		},
	},
	start: null,
	_wpnonce: 'test-nonce',
};

describe( 'route.tsx', () => {
	beforeEach( () => {
		// Reset all mocks
		jest.clearAllMocks();

		// Mock window.wcSettings
		window.wcSettings = {
			admin: {
				settingsData: mockSettingsPages,
			},
		};

		( useContext as jest.Mock ).mockReturnValue( {
			settingsData: mockSettingsPages,
		} );

		// Mock default location
		(
			privateApis as {
				useLocation: jest.Mock;
			}
		 ).useLocation.mockReturnValue( {
			params: { tab: 'general' },
		} );
	} );

	describe( 'useActiveRoute', () => {
		it( 'should return legacy route for non-modern pages', () => {
			const { result } = renderHook( () => useActiveRoute() );

			expect( result.current.route.key ).toBe( 'general' );
			expect( result.current.route.areas.content ).toBeDefined();
			expect( result.current.route.areas.sidebar ).toBeDefined();

			render( result.current.route.areas.sidebar as JSX.Element );
			expect(
				screen.getByTestId( 'sidebar-navigation-screen' )
			).toBeInTheDocument();

			expect( result.current.route.areas.edit ).toBeNull();
		} );

		it( 'should return not found route for non-existent pages', () => {
			// Mock location for non-existent page
			(
				privateApis as {
					useLocation: jest.Mock;
				}
			 ).useLocation.mockReturnValue( {
				params: { tab: 'non-existent' },
			} );

			const { result } = renderHook( () => useActiveRoute() );

			expect( result.current.route.key ).toBe( 'non-existent' );
			render( result.current.route.areas.content as JSX.Element );
			expect( screen.getByText( 'Page not found' ) ).toBeInTheDocument();
			expect( result.current.route.areas.sidebar ).toBeDefined();
		} );

		it( 'should return modern route for modern pages', () => {
			(
				privateApis as {
					useLocation: jest.Mock;
				}
			 ).useLocation.mockReturnValue( {
				params: { tab: 'modern' },
			} );

			// Mock a modern page
			const mockModernPages = {
				pages: {
					modern: {
						label: 'Modern',
						icon: 'published',
						slug: 'modern',
						sections: {},
						is_modern: true,
						start: null,
						end: null,
					},
				},
				start: null,
				_wpnonce: 'test-nonce',
			};

			( useContext as jest.Mock ).mockReturnValue( {
				settingsData: mockModernPages,
			} );

			( applyFilters as jest.Mock ).mockReturnValue( {
				modern: {
					areas: {
						content: <div>Modern Page</div>,
					},
				},
			} );

			const { result } = renderHook( () => useActiveRoute() );
			expect( result.current.route.key ).toBe( 'modern' );
			expect( result.current.route.areas.sidebar ).toBeDefined();
		} );
	} );

	describe( 'useModernRoutes', () => {
		it( 'should update routes when new hooks are added', () => {
			renderHook( () => useModernRoutes() );

			// Simulate hook added
			( didFilter as jest.Mock ).mockReturnValue( 1 );
			const hookAddedCallback = ( addAction as jest.Mock ).mock
				.calls[ 0 ][ 2 ];
			hookAddedCallback( 'woocommerce_admin_settings_pages' );

			expect( applyFilters ).toHaveBeenCalledWith(
				'woocommerce_admin_settings_pages',
				{}
			);
		} );

		it( 'should not update routes for unrelated hooks', () => {
			renderHook( () => useModernRoutes() );

			// Simulate unrelated hook added
			const hookAddedCallback = ( addAction as jest.Mock ).mock
				.calls[ 0 ][ 2 ];
			hookAddedCallback( 'unrelated_hook' );

			expect( applyFilters ).toHaveBeenCalledTimes( 1 ); // Only initial call
		} );
	} );
} );
