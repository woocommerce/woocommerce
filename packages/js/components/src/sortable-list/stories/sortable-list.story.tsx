/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, moreVertical, payment } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	SortableList,
	SortableListDefaultHandle,
	SortableListHandle,
	SortableListItem,
} from '../';
import './style.scss';
import '../style.scss';

const initialItems = [
	{ id: 'card', label: 'Credit card' },
	{ id: 'bank', label: 'Direct bank transfer' },
	{ id: 'cod', label: 'Cash on delivery' },
];

const paymentMethodItems = [
	{
		id: 'card',
		label: 'Credit card',
		description:
			'Let customers pay with major credit and debit cards at checkout.',
		fee: '2.9% + $0.30',
		status: 'Required',
	},
	{
		id: 'apple-google-pay',
		label: 'Apple Pay / Google Pay',
		description:
			'Offer faster checkout using saved cards and supported wallets.',
		fee: 'No additional fee',
		status: 'Express checkout',
	},
	{
		id: 'klarna',
		label: 'Klarna',
		description:
			'Give shoppers flexible buy now, pay later options at checkout.',
		fee: '5.99% + $0.30',
		status: 'Pending approval',
	},
];

export const Basic = () => {
	const [ items, setItems ] = useState( initialItems );

	return (
		<SortableList
			items={ items }
			onChange={ setItems }
			getItemId={ ( item ) => item.id }
			getItemLabel={ ( item ) => item.label }
		>
			{ ( { items: renderedItems, getItemId } ) =>
				renderedItems.map( ( item ) => (
					<SortableListItem key={ item.id } id={ getItemId( item ) }>
						<SortableListDefaultHandle />
						<span>{ item.label }</span>
					</SortableListItem>
				) )
			}
		</SortableList>
	);
};

export const CustomHandle = () => {
	const [ items, setItems ] = useState( initialItems );

	return (
		<SortableList
			items={ items }
			onChange={ setItems }
			getItemId={ ( item ) => item.id }
			getItemLabel={ ( item ) => item.label }
		>
			{ ( { items: renderedItems, getItemId } ) =>
				renderedItems.map( ( item ) => (
					<SortableListItem key={ item.id } id={ getItemId( item ) }>
						<SortableListHandle
							label={ sprintf(
								/* translators: %s: Sortable item label. */
								__( 'Drag %s to reorder', 'woocommerce' ),
								item.label
							) }
						>
							Move
						</SortableListHandle>
						<span>{ item.label }</span>
					</SortableListItem>
				) )
			}
		</SortableList>
	);
};

export const DisabledItems = () => {
	const [ items, setItems ] = useState( [
		{ id: 'cover', label: 'Cover image', locked: true },
		...initialItems,
	] );

	return (
		<SortableList
			items={ items }
			onChange={ setItems }
			getItemId={ ( item ) => item.id }
			getItemDisabled={ ( item ) => item.locked }
			getItemLabel={ ( item ) => item.label }
		>
			{ ( { items: renderedItems, getItemId, getItemDisabled } ) =>
				renderedItems.map( ( item ) => (
					<SortableListItem
						key={ item.id }
						id={ getItemId( item ) }
						disabled={ getItemDisabled( item ) }
					>
						<SortableListDefaultHandle />
						<span>{ item.label }</span>
					</SortableListItem>
				) )
			}
		</SortableList>
	);
};

export const Horizontal = () => {
	const [ items, setItems ] = useState( initialItems );

	return (
		<SortableList
			orientation="horizontal"
			items={ items }
			onChange={ setItems }
			getItemId={ ( item ) => item.id }
			getItemLabel={ ( item ) => item.label }
		>
			{ ( { items: renderedItems, getItemId } ) =>
				renderedItems.map( ( item ) => (
					<SortableListItem key={ item.id } id={ getItemId( item ) }>
						<SortableListDefaultHandle />
						<span>{ item.label }</span>
					</SortableListItem>
				) )
			}
		</SortableList>
	);
};

export const PaymentMethodRows = () => {
	const [ items, setItems ] = useState( paymentMethodItems );

	return (
		<SortableList
			className="woocommerce-sortable-list-story-payment-methods"
			items={ items }
			onChange={ setItems }
			getItemId={ ( item ) => item.id }
			getItemLabel={ ( item ) => item.label }
		>
			{ ( { items: renderedItems, getItemId } ) =>
				renderedItems.map( ( item ) => (
					<SortableListItem
						className="woocommerce-sortable-list-story-payment-method"
						key={ item.id }
						id={ getItemId( item ) }
					>
						<SortableListDefaultHandle
							label={ sprintf(
								/* translators: %s: Payment method label. */
								__( 'Drag %s to reorder', 'woocommerce' ),
								item.label
							) }
						/>
						<div className="woocommerce-sortable-list-story-payment-method__icon">
							<Icon icon={ payment } size={ 28 } />
						</div>
						<div className="woocommerce-sortable-list-story-payment-method__content">
							<div className="woocommerce-sortable-list-story-payment-method__header">
								<span className="woocommerce-sortable-list-story-payment-method__title">
									{ item.label }
								</span>
								<span className="woocommerce-sortable-list-story-payment-method__status">
									{ item.status }
								</span>
							</div>
							<div className="woocommerce-sortable-list-story-payment-method__description">
								{ item.description }
							</div>
						</div>
						<div className="woocommerce-sortable-list-story-payment-method__meta">
							{ item.fee }
						</div>
						<div className="woocommerce-sortable-list-story-payment-method__actions">
							<Button variant="secondary">
								{ __( 'Manage', 'woocommerce' ) }
							</Button>
							<Button
								aria-label={ sprintf(
									/* translators: %s: Payment method label. */
									__( 'More actions for %s', 'woocommerce' ),
									item.label
								) }
								icon={ moreVertical }
								variant="tertiary"
							>
								<span className="woocommerce-sortable-list-story-payment-method__screen-reader-text">
									{ sprintf(
										/* translators: %s: Payment method label. */
										__(
											'More actions for %s',
											'woocommerce'
										),
										item.label
									) }
								</span>
							</Button>
						</div>
					</SortableListItem>
				) )
			}
		</SortableList>
	);
};

export default {
	title: 'Components/SortableList',
	component: SortableList,
};
