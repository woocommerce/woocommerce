/**
 * External dependencies
 */
import { Button, Card, CardHeader } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { speak } from '@wordpress/a11y';
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
 * Pure UI wrapper for a dismissable recommendation card. Hides the `Card` when
 * `isDismissed` is true, otherwise wraps `children` in a `Card`.
 *
 * This component holds no persistence logic. Callers manage the dismissal state
 * with a hook and pass the resulting `isDismissed` here and `onDismiss` to the
 * nested {@link DismissableListHeading}.
 *
 * Dismissing the card unmounts whatever element held focus (the ellipsis menu
 * button or its popover), which would otherwise drop keyboard and screen reader
 * users onto `document.body` with no announcement. To keep them oriented, the
 * surrounding wrapper stays mounted as a focus target: on dismissal we move
 * focus to it and announce the change with `speak()`.
 */
export const DismissableList = ( {
	children,
	className,
	isDismissed,
}: {
	children: React.ReactNode;
	className?: string;
	/**
	 * Whether the card has been dismissed. When true the card is hidden.
	 */
	isDismissed?: boolean;
} ) => {
	const wrapperRef = useRef< HTMLDivElement >( null );
	// Seed with the initial value so an already-dismissed card on first render
	// is treated as steady state, not a fresh dismissal.
	const wasDismissed = useRef( isDismissed );

	useEffect( () => {
		if ( isDismissed && ! wasDismissed.current ) {
			speak( __( 'Recommendation hidden.', 'woocommerce' ), 'assertive' );
			wrapperRef.current?.focus();
		}

		wasDismissed.current = isDismissed;
	}, [ isDismissed ] );

	return (
		<div
			ref={ wrapperRef }
			// Programmatically focusable (not in the tab order) so focus can
			// land here once the card unmounts on dismissal.
			tabIndex={ -1 }
			className="woocommerce-dismissable-list__wrapper"
		>
			{ ! isDismissed && (
				<Card
					size="medium"
					className={ clsx(
						'woocommerce-dismissable-list',
						className
					) }
				>
					{ children }
				</Card>
			) }
		</div>
	);
};
