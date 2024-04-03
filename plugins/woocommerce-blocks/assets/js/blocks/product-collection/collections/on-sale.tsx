/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, percent } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
} from '../constants';
import {
	CoreCollectionNames,
	CoreFilterNames,
	HandlePreviewStateArgs,
} from '../types';

const collection = {
	name: CoreCollectionNames.ON_SALE,
	title: __( 'On Sale', 'woocommerce' ),
	icon: ( <Icon icon={ percent } /> ) as BlockIcon,
	description: __(
		'Highlight products that are currently on sale.',
		'woocommerce'
	),
	keywords: [ 'product collection' ],
	scope: [],
};

const attributes = {
	...DEFAULT_ATTRIBUTES,
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		...DEFAULT_ATTRIBUTES.query,
		inherit: false,
		woocommerceOnSale: true,
		perPage: 5,
		pages: 1,
	},
	collection: collection.name,
	hideControls: [ CoreFilterNames.INHERIT, CoreFilterNames.ON_SALE ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'On sale products', 'woocommerce' ),
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const innerBlocks: InnerBlockTemplate[] = [
	heading,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
];

/**
 * This is Async operation example to handle preview state.
 * I am just using setTimeout to simulate async operation, but it could be
 * any async operation like fetching data from server or doing a polling in every 5 seconds.
 */
const handlePreviewState = ( { setPreviewState }: HandlePreviewStateArgs ) => {
	const timeoutID = setTimeout( () => {
		setPreviewState( {
			isPreview: false,
			previewMessage: '',
		} );
	}, 5000 );

	return () => {
		clearTimeout( timeoutID );
	};
};

export default {
	...collection,
	attributes,
	innerBlocks,
	preview: {
		handlePreviewState,
		initialState: {
			isPreview: true,
			previewMessage: 'On sale collection is in preview mode',
		},
	},
};
