import { EmptyState, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import { store } from '@wordpress/icons';

/**
 * Empty state component for the store-activity widget.
 * Displayed when there's no recent activity to show.
 */
export function StoreActivityEmptyState() {
	return (
		<Stack
			direction="column"
			align="center"
			justify="center"
			className="store-activity-empty-state"
		>
			<EmptyState.Root>
				<EmptyState.Icon icon={ store } />
				<EmptyState.Title>
					{ __( 'No recent activity yet', 'woocommerce' ) }
				</EmptyState.Title>
				<EmptyState.Description>
					{ __(
						"You'll start seeing activity here soon — orders, bookings, reviews, and subscribers will appear as your store grows.",
						'woocommerce'
					) }
				</EmptyState.Description>
			</EmptyState.Root>
		</Stack>
	);
}
