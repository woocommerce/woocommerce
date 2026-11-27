/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useId } from '@wordpress/element';
import type { ReactElement, ReactNode } from 'react';

interface RoundedTotalInfoProps {
	/** How the total was rounded, e.g. "Rounded to £5.98 from 3 units priced £1.992."; null renders the total alone. */
	note: string | null;
	/** The rendered total. */
	children: ReactNode;
}

/**
 * A total that doesn't add up from the prices shown: a button with an info icon and the amount, which opens the note
 * in a tooltip.
 *
 * The markup matches the classic cart's (RoundedTotalNote.php). The tooltip is a native popover, so the browser opens
 * it on click or tap and closes it on Escape or a click elsewhere. The shared wc-rounded-total-info script and styles,
 * which the Cart, Checkout and Mini-Cart blocks enqueue, add hover and focus, placement and aria-expanded.
 */
const RoundedTotalInfo = ( {
	note,
	children,
}: RoundedTotalInfoProps ): ReactElement => {
	const tooltipId = useId();

	if ( ! note ) {
		return <>{ children }</>;
	}

	// React 18 doesn't know the popover attributes yet, so they're passed through as plain attributes.
	const buttonPopoverAttributes = {
		popovertarget: tooltipId,
		popovertargetaction: 'show',
	} as Record< string, string >;
	const tooltipPopoverAttributes = { popover: 'auto' } as Record<
		string,
		string
	>;

	return (
		<span className="wc-rounded-total-info">
			<button
				type="button"
				className="wc-rounded-total-info__button"
				aria-expanded="false"
				aria-describedby={ tooltipId }
				{ ...buttonPopoverAttributes }
			>
				<svg
					className="wc-rounded-total-info__icon"
					viewBox="0 0 16 16"
					width="16"
					height="16"
					fill="none"
					aria-hidden="true"
					focusable="false"
				>
					<circle
						cx="8"
						cy="8"
						r="6.5"
						stroke="currentColor"
						strokeWidth="1.3"
					/>
					<path
						d="M8 7.2v4"
						stroke="currentColor"
						strokeWidth="1.4"
						strokeLinecap="round"
					/>
					<circle cx="8" cy="4.9" r="0.9" fill="currentColor" />
				</svg>
				<span className="wc-rounded-total-info__amount">
					{ children }
				</span>
				<span className="wc-rounded-total-info__label">
					{ __( 'Rounding details', 'woocommerce' ) }
				</span>
			</button>
			<span
				id={ tooltipId }
				className="wc-rounded-total-info__tooltip"
				role="tooltip"
				{ ...tooltipPopoverAttributes }
			>
				{ note }
			</span>
		</span>
	);
};

export default RoundedTotalInfo;
