/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import React from 'react';

/**
 * Internal dependencies
 */
import { ListItem } from '..';

export const Basic = () => {
	return (
		<>
			<ListItem>Item 1</ListItem>
			<ListItem>Item 2</ListItem>
			<ListItem>Item 3</ListItem>
			<ListItem>Item 4</ListItem>
			<ListItem>Item 5</ListItem>
		</>
	);
};

export const Draggable = () => {
	return (
		<>
			<ListItem onDragStart={ () => null } onDragEnd={ () => null }>
				Item 1
			</ListItem>
			<ListItem onDragStart={ () => null } onDragEnd={ () => null }>
				Item 2
			</ListItem>
			<ListItem onDragStart={ () => null } onDragEnd={ () => null }>
				Item 3
			</ListItem>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/components/ListItem',
	component: ListItem,
};
