/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TreeSelectControl from '../index';

const treeSelectControlOptions = [
	{
		value: 'EU',
		label: 'Europe',
		children: [
			{ value: 'ES', label: 'Spain' },
			{ value: 'FR', label: 'France' },
			{ value: 'TR', label: 'Türkiye' },
			{ key: 'FR-Colonies', value: 'FR-C', label: 'France (Colonies)' },
		],
	},
	{
		value: 'AS',
		label: 'Asia',
		children: [
			{
				value: 'JP',
				label: 'Japan',
				children: [
					{
						value: 'TO',
						label: 'Tokyo',
						children: [
							{ value: 'SI', label: 'Shibuya' },
							{ value: 'GI', label: 'Ginza' },
						],
					},
					{ value: 'OK', label: 'Okinawa' },
				],
			},
			{ value: 'CH', label: 'China' },
			{
				value: 'MY',
				label: 'Malaysia',
				children: [ { value: 'KU', label: 'Kuala Lumpur' } ],
			},
		],
	},
	{
		value: 'NA',
		label: 'North America',
		children: [
			{
				value: 'US',
				label: 'United States',
				children: [
					{ value: 'NY', label: 'New York' },
					{ value: 'TX', label: 'Texas' },
					{ value: 'GE', label: 'Georgia' },
				],
			},
			{
				value: 'CA',
				label: 'Canada',
			},
		],
	},
];

const Template = ( args ) => {
	const [ selected, setSelected ] = useState( [ 'ES' ] );

	useEffect( () => {
		if ( args.onChange ) {
			args.onChange( selected );
		}
	}, [ selected ] );

	return (
		<TreeSelectControl
			{ ...args }
			value={ selected }
			onChange={ setSelected }
		/>
	);
};

export const Base = Template.bind( {} );

Base.args = {
	id: 'my-id',
	label: 'Select Countries',
	placeholder: 'Search countries',
	disabled: false,
	options: treeSelectControlOptions,
	maxVisibleTags: 3,
	selectAllLabel: 'All countries',
	includeParent: false,
	alwaysShowPlaceholder: false,
	individuallySelectParent: false,
	clearOnSelect: true,
};

Base.argTypes = {
	onInputChange: { action: 'onInputChange' },
	onChange: { action: 'onChange' },
};

export default {
	title: 'WooCommerce Admin/components/TreeSelectControl',
	component: TreeSelectControl,
};
