/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	AiAccessSettings,
	ANSWERS_OPTION,
	TRAINING_OPTION,
	type AiAccessData,
} from '..';
import { getAdminSetting } from '~/utils/admin-settings';
import { isFeatureEnabled } from '~/utils/features';

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );
jest.mock( '~/utils/features', () => ( {
	isFeatureEnabled: jest.fn(),
} ) );

const mockedSetting = getAdminSetting as jest.Mock;
const mockedFeature = isFeatureEnabled as jest.Mock;

const renderWith = ( data: AiAccessData | Record< string, never > ) => {
	mockedSetting.mockReturnValue( data );
	const onDirtyChange = jest.fn();
	const view = render( <AiAccessSettings onDirtyChange={ onDirtyChange } /> );
	return { ...view, onDirtyChange };
};

const hiddenInput = (
	container: ReturnType< typeof render >[ 'container' ],
	name: string
) => container.querySelector( `input[type="hidden"][name="${ name }"]` );

describe( 'AiAccessSettings', () => {
	beforeEach( () => {
		mockedFeature.mockReturnValue( true );
	} );

	it( 'renders nothing while the agent_readiness feature is off', () => {
		mockedFeature.mockReturnValue( false );
		const { container } = renderWith( { status: 'default' } );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing without data from the server', () => {
		const { container } = renderWith( {} );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the defaults with editable controls and no notice', () => {
		const { container } = renderWith( { status: 'default' } );

		const toggle = screen.getByRole( 'checkbox', {
			name: /Allow AI assistants to use your content in answers/,
		} );
		const disallow = screen.getByRole( 'radio', {
			name: /Don’t allow AI training/,
		} );
		expect( toggle ).toBeChecked();
		expect( toggle ).toBeEnabled();
		expect( disallow ).toBeChecked();
		expect( disallow ).toBeEnabled();
		expect( container.querySelector( '.components-notice' ) ).toBeNull();
		expect(
			hiddenInput( container, ANSWERS_OPTION )?.getAttribute( 'value' )
		).toBe( 'yes' );
		expect(
			hiddenInput( container, TRAINING_OPTION )?.getAttribute( 'value' )
		).toBe( 'no' );
	} );

	it( 'updates the posted values and reports changes', () => {
		const { container, onDirtyChange } = renderWith( {
			status: 'default',
			[ ANSWERS_OPTION ]: 'yes',
			[ TRAINING_OPTION ]: 'no',
		} );

		fireEvent.click(
			screen.getByRole( 'radio', { name: /^Allow AI training/ } )
		);
		expect(
			hiddenInput( container, TRAINING_OPTION )?.getAttribute( 'value' )
		).toBe( 'yes' );
		expect( onDirtyChange ).toHaveBeenLastCalledWith( true );

		fireEvent.click(
			screen.getByRole( 'radio', { name: /Don’t allow AI training/ } )
		);
		expect( onDirtyChange ).toHaveBeenLastCalledWith( false );
	} );

	it( 'shows the AI shopping notice only while answers are off and AI shopping is on', () => {
		const { container } = renderWith( {
			status: 'default',
			[ ANSWERS_OPTION ]: 'yes',
			aiShoppingEnabled: true,
			aiShoppingUrl:
				'/wp-admin/admin.php?page=wc-settings&tab=agentic-commerce&section=shopping',
		} );
		const toggle = screen.getByRole( 'checkbox', {
			name: /Allow AI assistants to use your content in answers/,
		} );
		expect( container.querySelector( '.components-notice' ) ).toBeNull();

		fireEvent.click( toggle );
		const notice = container.querySelector( '.components-notice' );
		expect( notice ).toHaveTextContent( /AI shopping is on/ );
		expect(
			screen.getByRole( 'link', { name: 'AI tools' } )
		).toHaveAttribute(
			'href',
			'/wp-admin/admin.php?page=wc-settings&tab=agentic-commerce&section=shopping'
		);

		fireEvent.click( toggle );
		expect( container.querySelector( '.components-notice' ) ).toBeNull();
	} );

	it( 'does not show the AI shopping notice while AI shopping is off', () => {
		const { container } = renderWith( {
			status: 'default',
			[ ANSWERS_OPTION ]: 'no',
			aiShoppingEnabled: false,
		} );

		expect( container.querySelector( '.components-notice' ) ).toBeNull();
	} );

	it( 'links to Jetpack instead of showing the controls while Jetpack manages AI access', () => {
		const { container } = renderWith( {
			status: 'jetpack',
			[ ANSWERS_OPTION ]: 'no',
			aiShoppingEnabled: true,
			jetpackUrl: '/wp-admin/admin.php?page=jetpack-seo',
		} );

		const notices = container.querySelectorAll(
			'.woocommerce-ai-access-settings__notice'
		);
		expect( notices ).toHaveLength( 1 );
		expect( notices[ 0 ] ).toHaveTextContent(
			'Jetpack manages AI access for your site.'
		);
		expect(
			screen.getByRole( 'link', { name: 'Change it in Jetpack' } )
		).toHaveAttribute( 'href', '/wp-admin/admin.php?page=jetpack-seo' );
		expect( screen.queryByRole( 'checkbox' ) ).toBeNull();
		expect( screen.queryByRole( 'radio' ) ).toBeNull();
		expect( hiddenInput( container, ANSWERS_OPTION ) ).toBeNull();
		expect( hiddenInput( container, TRAINING_OPTION ) ).toBeNull();
	} );

	it.each( [
		[ 'robots_file', /Your site’s robots\.txt file manages AI access/ ],
		[ 'other_plugin', /Another plugin manages AI access for your site/ ],
	] as const )(
		'shows only a warning in place of the controls for %s',
		( status, noticeText ) => {
			const { container } = renderWith( {
				status,
				[ ANSWERS_OPTION ]: 'no',
				aiShoppingEnabled: true,
			} );

			const notices = container.querySelectorAll(
				'.woocommerce-ai-access-settings__notice'
			);
			expect( notices ).toHaveLength( 1 );
			expect( notices[ 0 ] ).toHaveTextContent( noticeText );
			expect( screen.queryByRole( 'checkbox' ) ).toBeNull();
			expect( screen.queryByRole( 'radio' ) ).toBeNull();
			expect( hiddenInput( container, ANSWERS_OPTION ) ).toBeNull();
			expect( hiddenInput( container, TRAINING_OPTION ) ).toBeNull();
		}
	);
} );
