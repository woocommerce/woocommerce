/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Badge } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const statuses: Record<
	ProductEntityRecord[ 'status' ],
	{ label: string; intent?: React.ComponentProps< typeof Badge >[ 'intent' ] }
> = {
	draft: {
		label: __( 'Draft', 'woocommerce' ),
		intent: 'draft',
	},
	publish: {
		label: __( 'Active', 'woocommerce' ),
		intent: 'stable',
	},
	trash: {
		label: __( 'Trash', 'woocommerce' ),
		intent: 'none',
	},
	'auto-draft': {
		label: __( 'Draft', 'woocommerce' ),
		intent: 'draft',
	},
};

export const ProductStatusBadge = ( {
	status,
}: {
	status: ProductEntityRecord[ 'status' ];
} ) => {
	const statusData = statuses[ status ];

	if ( ! statusData ) {
		return <Badge intent="none">{ __( 'Unknown', 'woocommerce' ) }</Badge>;
	}

	return <Badge intent={ statusData.intent }>{ statusData.label }</Badge>;
};
