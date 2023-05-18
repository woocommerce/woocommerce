/**
 * External dependencies
 */
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { DefaultItemType } from '../hooks/use-dropdown';
import { SelectControl } from '../';

const sampleItems: DefaultItemType[] = [
	{ value: 'apple', label: 'Apple' },
	{ value: 'pear', label: 'Pear' },
	{ value: 'orange', label: 'Orange' },
	{ value: 'grape', label: 'Grape' },
	{ value: 'banana', label: 'Banana' },
];

export const Single: React.FC = () => {
	return (
		<>
			<SelectControl items={ sampleItems } label="Single value" />
		</>
	);
};

export const Multiple: React.FC = () => {
	return (
		<>
			<SelectControl
				items={ sampleItems }
				label="Multiple values"
				multiple
			/>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectControlV2',
	component: SelectControl,
};
