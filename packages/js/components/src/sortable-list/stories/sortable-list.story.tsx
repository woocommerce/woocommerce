/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	SortableList,
	SortableListDefaultHandle,
	SortableListHandle,
	SortableListItem,
} from '../';
import '../style.scss';

const initialItems = [
	{ id: 'card', label: 'Credit card' },
	{ id: 'bank', label: 'Direct bank transfer' },
	{ id: 'cod', label: 'Cash on delivery' },
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

export default {
	title: 'Components/SortableList',
	component: SortableList,
};
