import { beforeAll, describe, expect, test, vi } from 'vitest';

/**
 * External dependencies
 */
import { WCUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { renderEmbeddedLayout } from '../render-embedded-layout';

// Mock dependencies
vi.mock( '@wordpress/element', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/element' ) ),
		createRoot: vi.fn( () => ( {
			render: vi.fn(),
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		/* eslint-disable @typescript-eslint/no-unused-vars */
		withCurrentUserHydration: vi.fn(
			( user ) => ( Component: React.ReactNode ) => Component
		),
		withSettingsHydration: vi.fn(
			( group, settings ) => ( Component: React.ReactNode ) => Component
		),
		/* eslint-enable @typescript-eslint/no-unused-vars */
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../layout', () => {
	const mock = {
		EmbedLayout: vi.fn( () => null ),
		PrimaryLayout: vi.fn( () => null ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../', () => {
	const mock = {
		EmbeddedBodyLayout: vi.fn( () => null ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'embedded-layout', () => {
	let mockEmbeddedRoot: HTMLDivElement;
	const mockHydrateUser = {
		woocommerce_meta: {},
	} as WCUser;
	const mockSettingsGroup = 'test-settings';
	beforeAll( () => {
		// Setup DOM elements
		mockEmbeddedRoot = document.createElement( 'div' );
		document.body.innerHTML = `
            <div id="wpbody-content">
                <div class="wrap woocommerce"></div>
            </div>
        `;
	} );
	test( 'should initialize embedded layout successfully', () => {
		const result = renderEmbeddedLayout(
			mockEmbeddedRoot,
			mockHydrateUser,
			mockSettingsGroup
		);

		// Verify embedded root class is removed
		expect(
			mockEmbeddedRoot.classList.contains( 'is-embed-loading' )
		).toBeFalsy();
		expect( result ).toBeTruthy();
	} );
	test( 'should handle missing wpbody-content', () => {
		document.body.innerHTML = '';
		const result = renderEmbeddedLayout(
			mockEmbeddedRoot,
			mockHydrateUser,
			mockSettingsGroup
		);
		expect( result ).toBeFalsy();
	} );
	test( 'should handle missing wrap element', () => {
		document.body.innerHTML = '<div id="wpbody-content"></div>';
		const result = renderEmbeddedLayout(
			mockEmbeddedRoot,
			mockHydrateUser,
			mockSettingsGroup
		);
		expect( result ).toBeFalsy();
	} );
} );
