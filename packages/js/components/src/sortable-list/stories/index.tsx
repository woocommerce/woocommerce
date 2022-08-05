/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import React, { DragEventHandler } from 'react';
import { Icon, wordpress } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { SortableList } from '..';
import { Handle } from '../handle';

export const Basic = () => {
	return (
		<SortableList
			onOrderChange={ ( items ) =>
				// eslint-disable-next-line no-alert
				alert( 'Order changed: ' + items.map( ( item ) => item.key ) )
			}
		>
			<Fragment key={ 'item-1' }>Item 1</Fragment>
			<Fragment key={ 'item-2' }>Item 2</Fragment>
			<Fragment key={ 'item-3' }>Item 3</Fragment>
			<Fragment key={ 'item-4' }>Item 4</Fragment>
			<Fragment key={ 'item-5' }>Item 5</Fragment>
		</SortableList>
	);
};

export const CustomHandle = () => {
	type CustomListItemProps = {
		children: React.ReactNode;
		onDraggableEnd?: DragEventHandler< Element >;
		onDraggableStart?: DragEventHandler< Element >;
	};
	const CustomListItem = ( {
		children,
		onDraggableStart,
		onDraggableEnd,
	}: CustomListItemProps ) => {
		return (
			<>
				<Handle
					onDragEnd={ onDraggableEnd }
					onDragStart={ onDraggableStart }
				>
					<Icon icon={ wordpress } size={ 16 } />
				</Handle>
				{ children }
			</>
		);
	};
	return (
		<SortableList shouldRenderHandles={ false }>
			<CustomListItem key="item-1">Item 1</CustomListItem>
			<CustomListItem key="item-2">Item 2</CustomListItem>
			<CustomListItem key="item-3">Item 3</CustomListItem>
			<CustomListItem key="item-4">Item 4</CustomListItem>
			<CustomListItem key="item-5">Item 5</CustomListItem>
		</SortableList>
	);
};

export default {
	title: 'WooCommerce Admin/components/SortableList',
	component: SortableList,
};
