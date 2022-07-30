/**
 * External dependencies
 */
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { SearchControl } from '../';

const items = [
	{ value: 'apple' },
	{ value: 'pear' },
	{ value: 'orange' },
	{ value: 'grape' },
	{ value: 'banana!!!' },
];

export const Single: React.FC = () => (
	<>
		<SearchControl items={ items } label="Single value" />
	</>
);

export const Multiple: React.FC = () => (
	<>
		<SearchControl hasMultiple items={ items } label="Multiple values" />
	</>
);

export default {
	title: 'WooCommerce Admin/components/SearchControl',
	component: SearchControl,
};
