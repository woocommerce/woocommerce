/**
 * External dependencies
 */
import { WCUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { renderEmbeddedLayout } from '../render-embedded-layout';

// Mock dependencies
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	createRoot: jest.fn( () => ( {
		render: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	/* eslint-disable @typescript-eslint/no-unused-vars */
	withCurrentUserHydration: jest.fn(
		( user ) => ( Component: React.ReactNode ) => Component
	),
	withSettingsHydration: jest.fn(
		( group, settings ) => ( Component: React.ReactNode ) => Component
	),
	/* eslint-enable @typescript-eslint/no-unused-vars */
} ) );

jest.mock( '../../layout', () => ( {
	EmbedLayout: jest.fn( () => null ),
	PrimaryLayout: jest.fn( () => null ),
} ) );

jest.mock( '../', () => ( {
	EmbeddedBodyLayout: jest.fn( () => null ),
} ) );

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
