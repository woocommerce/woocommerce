/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import React, { DragEventHandler } from 'react';
import { Icon, wordpress } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Sortable, SortableHandle } from '..';
import { ListItem } from '../../list-item';

export const Basic = () => {
	return (
		<Sortable
			onOrderChange={ ( items ) =>
				// eslint-disable-next-line no-console
				console.log(
					'Order changed: ' + items.map( ( item ) => item.key )
				)
			}
		>
			<ListItem key={ 'item-1' }>Item 1</ListItem>
			<ListItem key={ 'item-2' }>Item 2</ListItem>
			<ListItem key={ 'item-3' }>Item 3</ListItem>
			<ListItem key={ 'item-4' }>Item 4</ListItem>
			<ListItem key={ 'item-5' }>Item 5</ListItem>
		</Sortable>
	);
};

export const CustomHandle = () => {
	type CustomListItemProps = {
		children: React.ReactNode;
		onDragEnd?: DragEventHandler< Element >;
		onDragStart?: DragEventHandler< Element >;
	};
	const CustomListItem = ( { children }: CustomListItemProps ) => {
		return (
			<>
				<SortableHandle>
					<Icon icon={ wordpress } size={ 16 } />
				</SortableHandle>
				{ children }
			</>
		);
	};
	return (
		<Sortable>
			<CustomListItem key="item-1">Item 1</CustomListItem>
			<CustomListItem key="item-2">Item 2</CustomListItem>
			<CustomListItem key="item-3">Item 3</CustomListItem>
			<CustomListItem key="item-4">Item 4</CustomListItem>
			<CustomListItem key="item-5">Item 5</CustomListItem>
		</Sortable>
	);
};

export default {
	title: 'WooCommerce Admin/components/Sortable',
	component: Sortable,
};
