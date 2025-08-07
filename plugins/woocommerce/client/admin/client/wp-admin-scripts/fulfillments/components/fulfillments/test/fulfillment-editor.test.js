/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import FulfillmentEditor from '../fulfillment-editor';

jest.mock( '@wordpress/components', () => ( {
	Button: ( { onClick, children } ) => (
		<button data-testid="button" onClick={ onClick }>
			{ children }
		</button>
	),
	Icon: ( { icon } ) => <span data-testid="icon">{ icon }</span>,
} ) );
jest.mock(
	'../../action-buttons/edit-fulfillment-button',
	() =>
		( { onClick } ) =>
			(
				<button
					data-testid="edit-fulfillment-button"
					onClick={ onClick }
				>
					Edit
				</button>
			)
);
jest.mock( '../../action-buttons/fulfill-items-button', () => () => (
	<button data-testid="fulfill-items-button">Fulfill items</button>
) );
jest.mock( '../../action-buttons/cancel-link', () => ( { onClick } ) => (
	<button data-testid="cancel-link" onClick={ onClick }>
		Cancel
	</button>
) );
jest.mock( '../../action-buttons/remove-button', () => () => (
	<button data-testid="remove-button">Remove</button>
) );
jest.mock( '../../action-buttons/update-button', () => () => (
	<button data-testid="update-button">Update</button>
) );
jest.mock( '../item-selector', () => () => (
	<div data-testid="item-selector" />
) );
jest.mock( '../fulfillment-status-badge', () => () => (
	<div data-testid="fulfillment-status-badge" />
) );
jest.mock( '../../customer-notification-form', () => () => (
	<div data-testid="fulfillment-customer-notification-form" />
) );

describe( 'FulfillmentEditor', () => {
	const mockProps = {
		index: 0,
		expanded: false,
		onExpand: jest.fn(),
		onCollapse: jest.fn(),
		fulfillment: {
			id: 1,
			status: 'unfulfilled',
			is_fulfilled: false,
			meta_data: [
				{
					id: 1,
					key: '_items',
					value: [
						{
							item_id: 1,
							qty: 2,
						},
						{
							item_id: 2,
							qty: 1,
						},
					],
				},
			],
		},
		fulfillments: [
			{
				id: 1,
				status: 'pending',
				is_fulfilled: false,
				meta_data: [
					{
						id: 1,
						key: '_items',
						value: [
							{
								item_id: 1,
								qty: 2,
							},
							{
								item_id: 2,
								qty: 1,
							},
						],
					},
				],
			},
			{
				id: 2,
				status: 'unfulfilled',
				is_fulfilled: false,
				meta_data: [
					{
						id: 1,
						key: '_items',
						value: [
							{
								item_id: 1,
								qty: 2,
							},
							{
								item_id: 2,
								qty: 1,
							},
						],
					},
				],
			},
		],
		order: {
			id: 1,
			currency: 'USD',
			line_items: [
				{
					id: 1,
					name: 'Item 1',
					quantity: 2,
					image: { src: 'example.png' },
				},
				{
					id: 2,
					name: 'Item 2',
					quantity: 1,
					image: { src: 'example.png' },
				},
			],
		},
	};

	it( 'renders the header and status badge', () => {
		render( <FulfillmentEditor { ...mockProps } /> );
		expect( screen.getByText( 'Fulfillment #1' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'fulfillment-status-badge' )
		).toBeInTheDocument();
	} );

	it( 'calls onExpand when header is clicked and not expanded', () => {
		const { container } = render( <FulfillmentEditor { ...mockProps } /> );
		fireEvent.click(
			container.querySelector(
				'.woocommerce-fulfillment-stored-fulfillment-list-item-header'
			)
		);
		expect( mockProps.onExpand ).toHaveBeenCalled();
	} );

	it( 'calls onCollapse when header is clicked and expanded', () => {
		const { container } = render(
			<FulfillmentEditor { ...mockProps } expanded={ true } />
		);
		fireEvent.click(
			container.querySelector(
				'.woocommerce-fulfillment-stored-fulfillment-list-item-header'
			)
		);
		expect( mockProps.onCollapse ).toHaveBeenCalled();
	} );
	it( 'doesn`t show the buttons when fulfillment is locked - default message', () => {
		const lockMetadata = [
			{
				id: 2,
				key: '_is_locked',
				value: true,
			},
		];
		const lockedProps = {
			...mockProps,
			expanded: true,
			fulfillment: {
				...mockProps.fulfillment,
				meta_data: [
					...mockProps.fulfillment.meta_data,
					...lockMetadata,
				],
			},
			fulfillments: [
				{
					...mockProps.fulfillments[ 0 ],
					meta_data: [
						...mockProps.fulfillments[ 0 ].meta_data,
						...lockMetadata,
					],
				},
			],
		};
		render( <FulfillmentEditor { ...lockedProps } /> );
		expect(
			screen.queryByTestId( 'edit-fulfillment-button' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'fulfill-items-button' )
		).not.toBeInTheDocument();
		expect( screen.queryByTestId( 'cancel-link' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'remove-button' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'update-button' )
		).not.toBeInTheDocument();
		// Check that the lock message is displayed
		expect(
			screen.getByText( 'This item is locked and cannot be edited.' )
		).toBeInTheDocument();
	} );
	it( 'doesn`t show the buttons when fulfillment is locked - custom message', () => {
		const lockMetadata = [
			{
				id: 2,
				key: '_is_locked',
				value: true,
			},
			{
				id: 3,
				key: '_lock_message',
				value: 'This fulfillment is locked.',
			},
		];
		const lockedProps = {
			...mockProps,
			expanded: true,
			fulfillment: {
				...mockProps.fulfillment,
				meta_data: [
					...mockProps.fulfillment.meta_data,
					...lockMetadata,
				],
			},
			fulfillments: [
				{
					...mockProps.fulfillments[ 0 ],
					meta_data: [
						...mockProps.fulfillments[ 0 ].meta_data,
						...lockMetadata,
					],
				},
			],
		};
		render( <FulfillmentEditor { ...lockedProps } /> );
		expect(
			screen.queryByTestId( 'edit-fulfillment-button' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'fulfill-items-button' )
		).not.toBeInTheDocument();
		expect( screen.queryByTestId( 'cancel-link' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'remove-button' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'update-button' )
		).not.toBeInTheDocument();
		// Check that the lock message is displayed
		expect(
			screen.getByText( 'This fulfillment is locked.' )
		).toBeInTheDocument();
	} );
} );
