/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DraggableList } from '../';

export const Basic = () => (
	<DraggableList
		onDragStart={ () => console.log( 'dragging' ) }
		onDragEnd={ () => console.log( 'drag complete' ) }
	>
		<span>Item 1</span>
		<span>Item 2</span>
		<span>Item 3</span>
	</DraggableList>
);

export default {
	title: 'WooCommerce Admin/components/DraggableList',
	component: DraggableList,
};
