import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Link } from '@wordpress/ui';
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
 *
 * Uses `@wordpress/ui`'s `Link` for the styled anchor — it forwards standard
 * anchor props to an `<a>` and carries WPDS tokens (brand color, focus ring).
 * For SPA-internal destinations (routes owned by the dashboard SPA) consumers
 * should prefer `Link` / `useNavigate` from `@wordpress/route` instead; here
 * the target is the legacy `post.php` admin screen, which is outside the SPA.
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
					{ orderLink: <Link href={ href } /> }
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
				{ orderLink: <Link href={ href } /> }
			) }
		</span>
	);
}
