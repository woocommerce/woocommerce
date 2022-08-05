/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import React from 'react';
import { Icon, wordpress } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DraggableList } from '../';
import { Handle } from '../handle';

export const Basic = () => {
	return (
		<DraggableList>
			<>Item 1</>
			<>Item 2</>
			<>Item 3</>
			<>Item 4</>
			<>Item 5</>
		</DraggableList>
	);
};

export const CustomHandle = () => {
	const renderItem = ( item ) => {
		return ( { onDraggableStart, onDraggableEnd } ) => {
			return (
				<span>
					<Handle
						onDragEnd={ onDraggableEnd }
						onDragStart={ onDraggableStart }
					>
						<Icon icon={ wordpress } size={ 16 } />
					</Handle>
					{ item }
				</span>
			);
		};
	};
	return (
		<DraggableList>
			{ renderItem( 'Item 1' ) }
			{ renderItem( 'Item 2' ) }
			{ renderItem( 'Item 3' ) }
			{ renderItem( 'Item 4' ) }
			{ renderItem( 'Item 5' ) }
		</DraggableList>
	);
};

export default {
	title: 'WooCommerce Admin/components/DraggableList',
	component: DraggableList,
};
