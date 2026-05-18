import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { OrderRecord } from '../../data';

type OrderTimelineEventProps = {
	order: OrderRecord;
};

function formatCustomerName( billing: OrderRecord[ 'billing' ] ): string {
	const first = billing?.first_name?.trim() ?? '';
	const last = billing?.last_name?.trim() ?? '';
	return `${ first } ${ last }`.trim();
}

/**
 * Renders the content of a single Order activity event in the Store Activity
 * timeline.
 */
export function OrderTimelineEvent( { order }: OrderTimelineEventProps ) {
	const href = `/wp-admin/post.php?post=${ order.id }&action=edit`;
	const displayNumber = order.number || String( order.id );
	const customerName = formatCustomerName( order.billing );

	if ( customerName ) {
		return (
			<span>
				{ createInterpolateElement(
					sprintf(
						// translators: %1$s is the order number, %2$s the customer name.
						__(
							'<orderLink>Order #%1$s</orderLink> placed by %2$s',
							'woocommerce'
						),
						displayNumber,
						customerName
					),
					{ orderLink: <a href={ href } /> }
				) }
			</span>
		);
	}

	return (
		<span>
			{ createInterpolateElement(
				sprintf(
					// translators: %s is the order number.
					__( '<orderLink>Order #%s</orderLink> placed', 'woocommerce' ),
					displayNumber
				),
				{ orderLink: <a href={ href } /> }
			) }
		</span>
	);
}
