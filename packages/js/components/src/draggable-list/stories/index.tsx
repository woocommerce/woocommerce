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
		<li>Item 1</li>
		<li>Item 2</li>
	</DraggableList>
);

export default {
	title: 'WooCommerce Admin/components/DraggableList',
	component: DraggableList,
};
