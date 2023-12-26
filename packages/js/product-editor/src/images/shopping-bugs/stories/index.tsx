/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { ShoppingBags } from '../index';

export default {
	title: 'Product Editor/images/ShoppingBags',
	component: ShoppingBags,
	args: {
		size: '88',
		colorOne: '#E0E0E0',
		colorTwo: '#F0F0F0',
	},
};

export const Default = ( args ) => <ShoppingBags { ...args } />;

// Set the story name
Default.storyName = 'Shopping Bags';
