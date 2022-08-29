/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import React from 'react';

/**
 * Internal dependencies
 */
import { ListItem } from '..';
import { Sortable } from '../../sortable';

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
		<Sortable>
			<ListItem>Item 1</ListItem>
			<ListItem>Item 2</ListItem>
			<ListItem>Item 3</ListItem>
		</Sortable>
	);
};

export default {
	title: 'WooCommerce Admin/components/ListItem',
	component: ListItem,
};
