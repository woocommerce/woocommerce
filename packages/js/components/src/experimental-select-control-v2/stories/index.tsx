/**
 * External dependencies
 */
import React, { createElement, useState } from 'react';

/**
 * Internal dependencies
 */
import { DefaultItem } from '../types';
import { SelectControl } from '../';

const sampleOptions: DefaultItem[] = [
	{ value: 'apple', label: 'Apple' },
	{ value: 'pear', label: 'Pear' },
	{ value: 'orange', label: 'Orange' },
	{ value: 'grape', label: 'Grape' },
	{ value: 'banana', label: 'Banana' },
];

export const Single: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItem | null >( null );

	return (
		<>
			<SelectControl
				options={ sampleOptions }
				label="Single value"
				selected={ selected }
				onSelect={ ( option ) => setSelected( option ) }
			/>
		</>
	);
};

export const Multiple: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItem[] >( [] );

	return (
		<>
			<SelectControl
				multiple
				options={ sampleOptions }
				label="Multiple values"
				selected={ selected }
				onSelect={ ( option ) =>
					setSelected( [ ...selected, option ] )
				}
				onDeselect={ ( option ) =>
					setSelected( selected.filter( ( o ) => o !== option ) )
				}
			/>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectControlV2',
	component: SelectControl,
};
