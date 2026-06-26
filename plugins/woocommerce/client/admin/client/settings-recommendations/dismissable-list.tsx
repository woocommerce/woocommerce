/**
 * External dependencies
 */
import { Button, Card, CardHeader } from '@wordpress/components';
import { EllipsisMenu } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './dismissable-list.scss';

/**
 * Presentational heading for a {@link DismissableList}. Renders the card header
 * and an ellipsis menu with a "Hide this" action that invokes `onDismiss`.
 *
 * Persistence is intentionally not handled here — the parent owns the dismissal
 * state and supplies `onDismiss` (typically from a dismiss hook such as
 * `useOptionDismiss` or `useEndpointDismiss`).
 */
export const DismissableListHeading = ( {
	onDismiss = () => null,
	children,
}: {
	children: React.ReactNode;
	onDismiss?: () => void;
} ) => {
	return (
		<CardHeader>
			<div className="woocommerce-dismissable-list__header">
				{ children }
			</div>
			<div>
				<EllipsisMenu
					label={ __( 'Task List Options', 'woocommerce' ) }
					renderContent={ () => (
						<div className="woocommerce-dismissable-list__controls">
							<Button onClick={ onDismiss }>
								{ __( 'Hide this', 'woocommerce' ) }
							</Button>
						</div>
					) }
				/>
			</div>
		</CardHeader>
	);
};

/**
 * Pure UI wrapper for a dismissable recommendation card. Renders nothing when
 * `isDismissed` is true, otherwise wraps `children` in a `Card`.
 *
 * This component holds no persistence logic. Callers manage the dismissal state
 * with a hook and pass the resulting `isDismissed` here and `onDismiss` to the
 * nested {@link DismissableListHeading}.
 */
export const DismissableList = ( {
	children,
	className,
	isDismissed,
}: {
	children: React.ReactNode;
	className?: string;
	/**
	 * Whether the card has been dismissed. When true the component renders null.
	 */
	isDismissed?: boolean;
} ) => {
	if ( isDismissed ) {
		return null;
	}

	return (
		<Card
			size="medium"
			className={ clsx( 'woocommerce-dismissable-list', className ) }
		>
			{ children }
		</Card>
	);
};
