/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/primitives';
import { Icon } from '@wordpress/icons';
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

export const handPickedIcon = (
	<SVG
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<Path
			d="M8.85074 4.8213L7.64702 3.92627L5.56365 6.72818L4.44959 5.89735L3.55286 7.0998L5.87107 8.82862L8.85074 4.8213Z"
			fill="currentColor"
		/>
		<Path d="M20 7.75004H11.1111V6.25004H20V7.75004Z" fill="currentColor" />
		<Path d="M20 12.75H11.1111V11.25H20V12.75Z" fill="currentColor" />
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8955 7.10457 10 6 10C4.89543 10 4 10.8955 4 12C4 13.1046 4.89543 14 6 14ZM6 13C6.55229 13 7 12.5523 7 12C7 11.4478 6.55229 11 6 11C5.44772 11 5 11.4478 5 12C5 12.5523 5.44772 13 6 13Z"
			fill="currentColor"
		/>
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M8 17C8 18.1046 7.10457 19 6 19C4.89543 19 4 18.1046 4 17C4 15.8955 4.89543 15 6 15C7.10457 15 8 15.8955 8 17ZM7 17C7 17.5523 6.55229 18 6 18C5.44772 18 5 17.5523 5 17C5 16.4478 5.44772 16 6 16C6.55229 16 7 16.4478 7 17Z"
			fill="currentColor"
		/>
		<Path d="M11.1111 17.75H20V16.25H11.1111V17.75Z" fill="currentColor" />
	</SVG>
);

const collection = {
	name: CoreCollectionNames.HAND_PICKED,
	title: __( 'Hand-Picked Products', 'woocommerce' ),
	icon: <Icon icon={ handPickedIcon } />,
	description: __(
		'Select specific products to recommend to customers.',
		'woocommerce'
	),
	keywords: [
		'specific',
		'choose',
		'recommend',
		'handpicked',
		'hand picked',
	],
	scope: [ 'inserter', 'block' ] as BlockVariationScope[],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		orderBy: 'post__in',
	},
	hideControls: [
		CoreFilterNames.HAND_PICKED,
		CoreFilterNames.FILTERABLE,
		CoreFilterNames.ORDER,
	],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'Recommended products', 'woocommerce' ),
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const pagination: InnerBlockTemplate = [
	'core/query-pagination',
	{
		layout: {
			type: 'flex',
			justifyContent: 'center',
		},
	},
];

const innerBlocks: InnerBlockTemplate[] = [
	heading,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
	pagination,
];

export default {
	...collection,
	attributes,
	innerBlocks,
};
