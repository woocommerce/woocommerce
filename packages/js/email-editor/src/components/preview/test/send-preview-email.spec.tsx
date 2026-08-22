/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import {
	createRegistry,
	createReduxStore,
	RegistryProvider,
} from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
import { forwardRef } from '@wordpress/element';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { SendPreviewEmail } from '../send-preview-email';
import { SendingPreviewStatus, storeName } from '../../../store';
import * as actions from '../../../store/actions';
import { getInitialState } from '../../../store/initial-state';
import { reducer } from '../../../store/reducer';
import * as selectors from '../../../store/selectors';
import { State } from '../../../store/types';

jest.mock( '@wordpress/compose', () => ( {
	useViewportMatch: jest.fn(),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
} ) );

jest.mock( '@wordpress/editor', () => ( {
	store: { name: 'core/editor' },
} ) );

jest.mock( '@wordpress/preferences', () => ( {
	store: { name: 'core/preferences' },
} ) );

jest.mock( '@wordpress/blocks', () => ( {
	parse: jest.fn(),
	serialize: jest.fn(),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _hook: string, value: unknown ) => value ),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( value: string ) => value,
	sprintf: ( format: string, value: string ) => format.replace( '%s', value ),
} ) );

jest.mock( '@wordpress/components', () => {
	const TextControl = forwardRef<
		HTMLInputElement,
		React.InputHTMLAttributes< HTMLInputElement > & {
			__next40pxDefaultSize?: boolean;
			__nextHasNoMarginBottom?: boolean;
			onChange?: ( value: string ) => void; // eslint-disable-line @typescript-eslint/no-unused-vars
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

const renderWithPreviewState = (
	overrides: Partial< State[ 'preview' ] > = {}
) => {
	const requestSendingNewsletterPreview = jest.fn( () => ( {
		type: 'REQUEST_SENDING_NEWSLETTER_PREVIEW',
	} ) );
	const initialState = getInitialState();
	const store = createReduxStore( storeName, {
		actions: {
			...actions,
			requestSendingNewsletterPreview,
		},
		controls,
		selectors,
		reducer,
		initialState: {
			...initialState,
			preview: {
				...initialState.preview,
				isModalOpened: true,
				...overrides,
			},
		},
	} );
	const registry = createRegistry();
	registry.register( store );

	return {
		registry,
		requestSendingNewsletterPreview,
		...render(
			<RegistryProvider value={ registry }>
				<SendPreviewEmail />
			</RegistryProvider>
		),
	};
};

describe( 'SendPreviewEmail', () => {
	it( 'should render the modal with input and buttons', () => {
		renderWithPreviewState();
		expect( screen.getByTestId( 'modal' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'text-control' ) ).toBeInTheDocument();
	} );

	it( 'requests a preview email sent to the address entered by the user', async () => {
		const { registry, requestSendingNewsletterPreview } =
			renderWithPreviewState();

		await userEvent.type(
			screen.getByTestId( 'text-control' ),
			'test@example.com'
		);
		expect( registry.select( storeName ).getPreviewState() ).toMatchObject(
			{
				toEmail: 'test@example.com',
			}
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Send test email' } )
		);

		expect( requestSendingNewsletterPreview ).toHaveBeenCalledTimes( 1 );
		expect( requestSendingNewsletterPreview ).toHaveBeenCalledWith(
			'test@example.com'
		);
	} );

	it( 'should show error message when status is ERROR', () => {
		renderWithPreviewState( {
			sendingPreviewStatus: SendingPreviewStatus.ERROR,
			errorMessage: 'Server failure',
		} );
		expect(
			screen.getByText( /Sorry, we were unable to send this email/ )
		).toBeInTheDocument();
		expect(
			screen.getByText( /Error: Server failure/ )
		).toBeInTheDocument();
	} );

	it( 'should show success message when status is SUCCESS', () => {
		renderWithPreviewState( {
			sendingPreviewStatus: SendingPreviewStatus.SUCCESS,
		} );
		expect(
			screen.getByText( 'Test email sent successfully!' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'icon' ) ).toBeInTheDocument();
	} );

	it( 'should render nothing when modal is closed', () => {
		const { container } = renderWithPreviewState( {
			isModalOpened: false,
		} );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'should disable send button and show "Sending…" text when sending', () => {
		renderWithPreviewState( {
			isSendingPreviewEmail: true,
			toEmail: 'test@example.com',
		} );
		const sendButton = screen.getByRole( 'button', {
			name: /sending…/i,
		} );
		expect( sendButton ).toBeDisabled();
		expect( sendButton ).toHaveTextContent( 'Sending…' );
	} );
} );
