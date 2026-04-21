/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Badge } from '@wordpress/ui';
import { ProductStatus } from '@woocommerce/data';

type BadgeStatusConfig = {
	label: string;
	intent?: React.ComponentProps< typeof Badge >[ 'intent' ];
};

const statuses = {
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
	deleted: {
		label: __( 'Deleted', 'woocommerce' ),
		intent: 'none',
	},
	pending: {
		label: __( 'Pending', 'woocommerce' ),
		intent: 'none',
	},
	private: {
		label: __( 'Private', 'woocommerce' ),
		intent: 'none',
	},
	future: {
		label: __( 'Scheduled', 'woocommerce' ),
		intent: 'none',
	},
	any: {
		label: __( 'Any', 'woocommerce' ),
		intent: 'none',
	},
} satisfies Record< ProductStatus, BadgeStatusConfig >;

export const ProductStatusBadge = ( { status }: { status: ProductStatus } ) => {
	const statusData = statuses[ status ];

	if ( ! statusData ) {
		return <Badge intent="none">{ __( 'Unknown', 'woocommerce' ) }</Badge>;
	}

	return <Badge intent={ statusData.intent }>{ statusData.label }</Badge>;
};
