/**
 * External dependencies
 */
import React from 'react';
import {
	cleanup,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

jest.mock( '@woocommerce/ai', () => ( {
	__experimentalRequestJetpackToken: jest
		.fn()
		.mockResolvedValue( { token: 'mocked-token' } ),
} ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

/**
 * Internal dependencies
 */
import ChatModal from './chat-modal';

describe( 'ChatModal', () => {
	beforeEach( () => {
		global.localStorage.clear();
		jest.restoreAllMocks();
		apiFetch.mockReset();
		cleanup();
	} );
	it( 'renders ChatModal component', () => {
		render( <ChatModal onClose={ () => {} } /> );
		const chatModalElement = screen.getByText( 'Woo Wizard Assistant' );
		expect( chatModalElement ).toBeInTheDocument();
	} );
	it( 'displays onboarding message and example clickable questions when there are no messages', () => {
		const { getByText } = render( <ChatModal onClose={ () => {} } /> );

		expect(
			getByText(
				'Hi there! I am your helpful Woo AI Assistant, here to answer questions, find relevant documentation, and perform common store tasks. How can I help you today?'
			)
		).toBeInTheDocument();
		expect(
			getByText( 'Run a sales report for today.' )
		).toBeInTheDocument();
		expect(
			getByText(
				'Recommend a Woo Extension that enables selling subscription products on my store.'
			)
		).toBeInTheDocument();
		expect(
			getByText(
				'What are some tips to improve my store conversion rates?'
			)
		).toBeInTheDocument();
		expect( apiFetch ).toHaveBeenCalledTimes( 0 );
	} );
	it( 'displays user message submission in chat history', async () => {
		const mockResponse = {
			thread_id: 123,
			answer: 'test answer',
			status: 'success',
		};

		apiFetch.mockResolvedValueOnce( mockResponse );
		render( <ChatModal onClose={ () => {} } /> );

		const messageInput =
			screen.getByPlaceholderText( /Type your message/i );
		const testMessage = 'Hello, Woo Assistant';

		fireEvent.change( messageInput, { target: { value: testMessage } } );

		const sendButton = screen.getByText( /Send/i );
		fireEvent.click( sendButton );

		await waitFor( () => {
			expect( screen.getByText( testMessage ) ).toBeInTheDocument();
		} );

		await waitFor( () => {
			expect( screen.getByText( /test answer/i ) ).toBeInTheDocument();
		} );
		// Assert that the apiFetch was called to Woo AI Assistant endpoint.
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				url: expect.stringContaining( 'wpcom/v2/woo-wizard' ),
				method: 'POST',
				body: expect.any( FormData ),
			} )
		);
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect(
			screen.getByLabelText( /This was helpful/i )
		).toBeInTheDocument();
		expect(
			screen.getByLabelText( /This was not helpful/i )
		).toBeInTheDocument();
	} );
	it( 'displays error message in chat history on empty assistant answer', async () => {
		const mockResponse = {
			thread_id: 123,
			answer: '',
			status: 'success',
		};

		apiFetch.mockResolvedValueOnce( mockResponse );
		render( <ChatModal onClose={ () => {} } /> );

		const messageInput =
			screen.getByPlaceholderText( /Type your message/i );
		const testMessage = 'Hello';

		fireEvent.change( messageInput, { target: { value: testMessage } } );

		const sendButton = screen.getByText( /Send/i );
		fireEvent.click( sendButton );

		await waitFor( () => {
			expect( screen.getByText( testMessage ) ).toBeInTheDocument();
		} );

		// Assert that the apiFetch was called to Woo AI Assistant endpoint.
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				url: expect.stringContaining( 'wpcom/v2/woo-wizard' ),
				method: 'POST',
				body: expect.any( FormData ),
			} )
		);

		await waitFor( () => {
			expect(
				screen.getByText( /No message returned from assistant/i )
			).toBeInTheDocument();
		} );
	} );
	it( 'displays error message in chat history on missing assistant answer', async () => {
		const mockResponse = {
			thread_id: 123,
			status: 'success',
		};

		apiFetch.mockResolvedValueOnce( mockResponse );
		render( <ChatModal onClose={ () => {} } /> );

		const messageInput =
			screen.getByPlaceholderText( /Type your message/i );
		const testMessage = 'Hello there';

		fireEvent.change( messageInput, { target: { value: testMessage } } );

		const sendButton = screen.getByText( /Send/i );
		fireEvent.click( sendButton );

		await waitFor( () => {
			expect( screen.getByText( testMessage ) ).toBeInTheDocument();
		} );

		await waitFor( () => {
			expect(
				screen.getByText( /No message returned from assistant/i )
			).toBeInTheDocument();
		} );
	} );
	it( 'handles requires_action response correctly', async () => {
		apiFetch.mockResolvedValueOnce( {
			thread_id: 'test-thread-id',
			run_id: 'test-run-id',
			status: 'requires_action',
			answer: {
				function_name: 'makeWCRestApiCall',
				function_id: 'test-function',
				function_args: {
					path: '/wc/v3/coupons',
					httpVerb: 'POST',
					body: { code: '20off', amount: '20' },
				},
			},
		} );

		// Mock the WooCommerce REST API call
		apiFetch.mockResolvedValueOnce( {
			answer: {
				id: 355,
				code: '30offricflair',
				amount: '30.00',
				status: 'publish',
				email_restrictions: [ 'ric@flair.com' ],
			},
			status: 200,
		} );

		// Mock the OpenAI Submit Tool Output API call
		apiFetch.mockResolvedValueOnce( {
			data: 'Success',
			status: 200,
		} );

		// Mock the Summary OpenAI API output.
		apiFetch.mockResolvedValueOnce( {
			answer: 'Coupon created successfully',
			status: 200,
		} );

		render( <ChatModal onClose={ () => {} } /> );

		// Simulate user query.
		fireEvent.change( screen.getByPlaceholderText( /Type your message/ ), {
			target: { value: 'Create a coupon' },
		} );
		fireEvent.click( screen.getByText( 'Send' ) );

		// Wait for the assistant's response.
		await waitFor( () => {
			expect(
				screen.getByText( 'Coupon created successfully' )
			).toBeInTheDocument();
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 4 );

		// Assert that the apiFetch was called to Woo AI Assistant endpoint.
		expect( apiFetch.mock.calls[ 0 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				url: expect.stringContaining( 'wpcom/v2/woo-wizard' ),
				method: 'POST',
				body: expect.any( FormData ),
			} )
		);
		// Assert that apiFetch was called for the WooCommerce REST API.
		expect( apiFetch.mock.calls[ 1 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				path: '/wc/v3/coupons',
				method: 'POST',
				data: expect.any( Object ),
			} )
		);
		// Assert that apiFetch was called for the OpenAI submit-tool-output API.
		expect( apiFetch.mock.calls[ 2 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				url: expect.stringContaining(
					'wpcom/v2/woo-wizard/submit-tool-output'
				),
				method: 'POST',
				body: expect.any( FormData ),
			} )
		);
		// Assert that apiFetch was called to summarize the result of all of the steps taken.
		expect( apiFetch.mock.calls[ 3 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				url: expect.stringContaining( 'wpcom/v2/woo-wizard' ),
				method: 'POST',
				body: expect.any( FormData ),
			} )
		);
	} );
} );
