/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { StoreAlerts } from '../';
import { setAdminSetting } from '~/utils/admin-settings';

const alerts = [
	{
		title: 'Alert title 1',
		content: 'Alert content 1',
		status: 'unactioned',
		actions: [],
	},
	{
		title: 'Alert title 2',
		content: 'Alert content 2',
		status: 'unactioned',
		actions: [
			{
				id: 'action-1',
				name: 'action-1',
				label: 'Click me!',
				url: '#',
			},
		],
	},
];

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

describe( 'StoreAlerts', () => {
	it( 'should return null when no alerts exist', () => {
		useSelect.mockImplementation( () => {
			return {
				alerts: [],
				isLoading: false,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect( container.firstChild ).toBeNull();
	} );

	it( 'should show the placeholder when loading and preloaded alerts exist', () => {
		setAdminSetting( 'alertCount', 2 );
		useSelect.mockImplementation( () => {
			return {
				alerts,
				isLoading: true,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect(
			container.querySelector( '.is-placeholder' )
		).toBeInTheDocument();
	} );

	it( 'should show the alert title and content', () => {
		useSelect.mockImplementation( () => {
			return {
				alerts,
				isLoading: false,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect(
			container.querySelector( '.woocommerce-store-alerts__title' )
				.textContent
		).toBe( 'Alert title 1' );
		expect(
			container.querySelector( '.woocommerce-store-alerts__message' )
				.textContent
		).toBe( 'Alert content 1' );
	} );

	it( 'should not show the pagination for a single alert', () => {
		useSelect.mockImplementation( () => {
			return {
				alerts: [ alerts[ 0 ] ],
				isLoading: false,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect(
			container.querySelector( '.woocommerce-store-alerts__pagination' )
		).toBeNull();
	} );

	it( 'should show the pagination for multiple alerts', () => {
		useSelect.mockImplementation( () => {
			return {
				alerts,
				isLoading: false,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect(
			container.querySelector( '.woocommerce-store-alerts__pagination' )
		).toBeInTheDocument();
	} );

	it( 'should show the actions for an alert that contains actions', () => {
		useSelect.mockImplementation( () => {
			return {
				alerts: [ alerts[ 1 ] ],
				isLoading: false,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect(
			container.querySelector(
				'.components-button:not(.woocommerce-store-alerts__close)'
			).textContent
		).toBe( 'Click me!' );
		expect(
			container
				.querySelector(
					'.components-button:not(.woocommerce-store-alerts__close)'
				)
				.getAttribute( 'href' )
		).toBe( '#' );
		expect(
			container.querySelector( '.woocommerce-store-alerts__snooze' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the actions and snooze actions for snoozable alerts', () => {
		useSelect.mockImplementation( () => {
			return {
				alerts: [ { ...alerts[ 1 ], is_snoozable: true } ],
				isLoading: false,
			};
		} );
		const { container } = render( <StoreAlerts /> );

		expect(
			container.querySelector(
				'.components-button:not(.woocommerce-store-alerts__close)'
			).textContent
		).toBe( 'Click me!' );
		expect(
			container
				.querySelector(
					'.components-button:not(.woocommerce-store-alerts__close)'
				)
				.getAttribute( 'href' )
		).toBe( '#' );
		expect(
			container.querySelector( '.woocommerce-store-alerts__snooze' )
		).toBeInTheDocument();
	} );

	it( 'should show different alerts when clicking the pagination buttons', () => {
		useSelect.mockImplementation( () => {
			return { alerts, isLoading: false };
		} );
		const { container, getByLabelText, rerender } = render(
			<StoreAlerts />
		);

		expect(
			container.querySelector( '.woocommerce-store-alerts__title' )
				.textContent
		).toBe( 'Alert title 1' );

		fireEvent.click( getByLabelText( 'Next Alert' ) );

		rerender( <StoreAlerts /> );

		expect(
			container.querySelector( '.woocommerce-store-alerts__title' )
				.textContent
		).toBe( 'Alert title 2' );

		fireEvent.click( getByLabelText( 'Previous Alert' ) );

		rerender( <StoreAlerts /> );

		expect(
			container.querySelector( '.woocommerce-store-alerts__title' )
				.textContent
		).toBe( 'Alert title 1' );
	} );
} );
