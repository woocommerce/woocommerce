import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

type OrderTimelineEventProps = {
	order: {
		id: number;
	};
};

/**
 * Renders the content of a single Order activity event in the Store Activity
 * timeline.
 */
export function OrderTimelineEvent( { order }: OrderTimelineEventProps ) {
	const href = `/wp-admin/post.php?post=${ order.id }&action=edit`;

	return (
		<span>
			{ createInterpolateElement(
				sprintf(
					// translators: %d is the order number.
					__( '<orderLink>Order #%d</orderLink> placed', 'woocommerce' ),
					order.id
				),
				{
					orderLink: <a href={ href } />,
				}
			) }
		</span>
	);
}
