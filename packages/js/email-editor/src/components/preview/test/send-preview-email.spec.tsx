/* eslint-disable @woocommerce/dependency-group -- because we import mocks first, we deactivate this rule to avoid ESLint errors */
import '../../__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as dataModule from '@wordpress/data';
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SendPreviewEmail } from '../send-preview-email';
import { SendingPreviewStatus } from '../../../store';

jest.mock( '@wordpress/compose', () => ( {
	useViewportMatch: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => {
	const TextControl = forwardRef<
		HTMLInputElement,
		React.InputHTMLAttributes< HTMLInputElement > & {
			__next40pxDefaultSize?: boolean;
			__nextHasNoMarginBottom?: boolean;
			onChange?: ( value: string ) => void;
		}
	>( ( props, ref ) => {
		const {
			__next40pxDefaultSize,
			__nextHasNoMarginBottom,
			onChange,
			...rest
		} = props;

		return (
			<input
				data-testid="text-control"
				ref={ ref }
				{ ...rest }
				onChange={ ( e ) => onChange?.( e.target.value ) }
			/>
		);
	} );

	return {
		Modal: ( props: { children?: React.ReactNode } ) => (
			<div data-testid="modal">{ props.children }</div>
		),
		TextControl,
		Button: ( props: React.ComponentProps< 'button' > ) => (
			<button onClick={ props.onClick } disabled={ props.disabled }>
				{ props.children }
			</button>
		),
	};
} );

jest.mock( '@wordpress/icons', () => ( {
	Icon: ( props: React.HTMLAttributes< HTMLSpanElement > ) => (
		<span data-testid="icon" { ...props } />
	),
	check: 'check',
} ) );

jest.mock( '@wordpress/keycodes', () => ( {
	ENTER: 13,
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
	recordEventOnce: jest.fn(),
} ) );

const useDispatchMock = dataModule.useDispatch as jest.Mock;
const useSelectMock = dataModule.useSelect as jest.Mock;

interface PreviewState {
	toEmail: string;
	isSendingPreviewEmail: boolean;
	sendingPreviewStatus: string;
	isModalOpened: boolean;
	errorMessage: string;
}

const setupUseSelectMock = ( overrides: Partial< PreviewState > = {} ) => {
	useSelectMock.mockImplementation(
		(
			selector: (
				select: ( storeName: string ) => {
					getPreviewState: () => PreviewState;
				}
			) => unknown
		) =>
			selector( () => ( {
				getPreviewState: () => ( {
					toEmail: 'test@example.com',
					isSendingPreviewEmail: false,
					sendingPreviewStatus: '',
					isModalOpened: true,
					errorMessage: '',
					...overrides,
				} ),
			} ) )
	);
};

describe( 'SendPreviewEmail', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useDispatchMock.mockReturnValue( {
			requestSendingNewsletterPreview: jest.fn(),
			togglePreviewModal: jest.fn(),
			updateSendPreviewEmail: jest.fn(),
		} );
	} );

	it( 'should render the modal with input and buttons', () => {
		setupUseSelectMock();
		render( <SendPreviewEmail /> );
		expect( screen.getByTestId( 'modal' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'text-control' ) ).toBeInTheDocument();
	} );

	it( 'should show error message when status is ERROR', () => {
		setupUseSelectMock( {
			sendingPreviewStatus: SendingPreviewStatus.ERROR,
			errorMessage: 'Server failure',
		} );
		render( <SendPreviewEmail /> );
		expect(
			screen.getByText( /Sorry, we were unable to send this email/ )
		).toBeInTheDocument();
		expect(
			screen.getByText( /Error: Server failure/ )
		).toBeInTheDocument();
	} );

	it( 'should show success message when status is SUCCESS', () => {
		setupUseSelectMock( {
			sendingPreviewStatus: SendingPreviewStatus.SUCCESS,
		} );
		render( <SendPreviewEmail /> );
		expect(
			screen.getByText( 'Test email sent successfully!' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'icon' ) ).toBeInTheDocument();
	} );

	it( 'should render nothing when modal is closed', () => {
		setupUseSelectMock( {
			isModalOpened: false,
		} );
		const { container } = render( <SendPreviewEmail /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'should disable send button and show "Sending…" text when sending', () => {
		setupUseSelectMock( {
			isSendingPreviewEmail: true,
		} );
		render( <SendPreviewEmail /> );
		const sendButton = screen.getByRole( 'button', {
			name: /sending…/i,
		} );
		expect( sendButton ).toBeDisabled();
		expect( sendButton ).toHaveTextContent( 'Sending…' );
	} );
} );
