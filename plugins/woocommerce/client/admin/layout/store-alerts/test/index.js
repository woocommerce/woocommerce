/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

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

describe( 'StoreAlerts', () => {
	it( 'should return null when no alerts exist', () => {
		const { container } = render( <StoreAlerts alerts={ [] } /> );

		expect( container.firstChild ).toBeNull();
	} );

	it( 'should show the placeholder when loading and preloaded alerts exist', () => {
		setAdminSetting( 'alertCount', 2 );
		const { container } = render(
			<StoreAlerts isLoading alerts={ alerts } />
		);

		expect(
			container.querySelector( '.is-placeholder' )
		).toBeInTheDocument();
	} );

	it( 'should show the alert title and content', () => {
		const { container } = render( <StoreAlerts alerts={ alerts } /> );

		expect( container.querySelector( 'h2' ).textContent ).toBe(
			'Alert title 1'
		);
		expect(
			container.querySelector( '.woocommerce-store-alerts__message' )
				.textContent
		).toBe( 'Alert content 1' );
	} );

	it( 'should not show the pagination for a single alert', () => {
		const { container } = render(
			<StoreAlerts alerts={ [ alerts[ 0 ] ] } />
		);

		expect(
			container.querySelector( '.woocommerce-store-alerts__pagination' )
		).toBeNull();
	} );

	it( 'should show the pagination for multiple alerts', () => {
		const { container } = render( <StoreAlerts alerts={ alerts } /> );

		expect(
			container.querySelector( '.woocommerce-store-alerts__pagination' )
		).toBeInTheDocument();
	} );

	it( 'should show the actions for an alert that contains actions', () => {
		const { container } = render(
			<StoreAlerts alerts={ [ alerts[ 1 ] ] } />
		);

		expect(
			container.querySelector( '.components-button' ).textContent
		).toBe( 'Click me!' );
		expect(
			container
				.querySelector( '.components-button' )
				.getAttribute( 'href' )
		).toBe( '#' );
		expect(
			container.querySelector( '.woocommerce-store-alerts__snooze' )
		).not.toBeInTheDocument();
	} );

	it( 'should show the actions and snooze actions for snoozable alerts', () => {
		const { container } = render(
			<StoreAlerts
				alerts={ [ { ...alerts[ 1 ], is_snoozable: true } ] }
			/>
		);

		expect(
			container.querySelector( '.components-button' ).textContent
		).toBe( 'Click me!' );
		expect(
			container
				.querySelector( '.components-button' )
				.getAttribute( 'href' )
		).toBe( '#' );
		expect(
			container.querySelector( '.woocommerce-store-alerts__snooze' )
		).toBeInTheDocument();
	} );

	it( 'should show different alerts when clicking the pagination buttons', () => {
		const { container, getByLabelText, rerender } = render(
			<StoreAlerts alerts={ alerts } />
		);

		expect( container.querySelector( 'h2' ).textContent ).toBe(
			'Alert title 1'
		);

		fireEvent.click( getByLabelText( 'Next Alert' ) );

		rerender( <StoreAlerts alerts={ alerts } /> );

		expect( container.querySelector( 'h2' ).textContent ).toBe(
			'Alert title 2'
		);

		fireEvent.click( getByLabelText( 'Previous Alert' ) );

		rerender( <StoreAlerts alerts={ alerts } /> );

		expect( container.querySelector( 'h2' ).textContent ).toBe(
			'Alert title 1'
		);
	} );
} );
