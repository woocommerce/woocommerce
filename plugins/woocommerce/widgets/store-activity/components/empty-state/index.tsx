import { __ } from '@wordpress/i18n';

/**
 * Empty state component for the store-activity widget.
 * Displayed when there's no recent activity to show.
 */
export function StoreActivityEmptyState() {
	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				height: '100%',
				padding: '24px',
				textAlign: 'center',
			} }
		>
			<p>
				{ __(
					"You'll start seeing activity here soon — orders, bookings, reviews, and subscribers will appear as your store grows.",
					'woocommerce'
				) }
			</p>
		</div>
	);
}
