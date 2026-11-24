/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { SendPreview } from '../send-preview';

jest.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( { togglePreviewModal: jest.fn() } ),
	useSelect: ( mapSelect: ( select: () => object ) => unknown ) =>
		mapSelect( () => ( { getEmailPostType: () => 'woo_email' } ) ),
} ) );

jest.mock( '@wordpress/editor', () => ( {
	PluginPreviewMenuItem: ( props: { children?: React.ReactNode } ) => (
		<button>{ props.children }</button>
	),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn(),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( value: string ) => value,
} ) );

jest.mock( '@wordpress/icons', () => ( {
	external: 'external',
} ) );

jest.mock( '../send-preview-email', () => ( {
	SendPreviewEmail: () => <div data-testid="send-preview-modal" />,
} ) );

jest.mock( '../../../store/constants', () => ( {
	storeName: 'email-editor/editor',
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'SendPreview', () => {
	it( 'adds the menu item and the modal by default', () => {
		( applyFilters as jest.Mock ).mockImplementation(
			( _hook: string, value: unknown ) => value
		);
		render( <SendPreview /> );

		expect(
			screen.getByRole( 'button', { name: 'Send a test email' } )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'send-preview-modal' )
		).toBeInTheDocument();
	} );

	it( 'renders neither the menu item nor the modal when the enabled filter returns false for the email type', () => {
		( applyFilters as jest.Mock ).mockImplementation(
			( hook: string, value: unknown, postType: string ) =>
				hook === 'woocommerce_email_editor_send_preview_enabled'
					? postType !== 'woo_email'
					: value
		);
		const { container } = render( <SendPreview /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
