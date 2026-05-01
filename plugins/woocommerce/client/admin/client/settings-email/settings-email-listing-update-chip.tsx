/**
 * <UpdateAvailableChip> — RSM-140
 *
 * Custom filter chip rendered in the email list toolbar. Replaces the
 * DataView auto-rendered "Updates" filter chip so the visual treatment can
 * match the design handoff:
 *
 *  - Sparkle (star) icon, "Update available" label, and a count badge.
 *  - Resting: white bg, neutral border, blueberry-filled count badge.
 *  - Active: tinted blueberry surface, blueberry border + foreground.
 *  - Hidden when count = 0 (per design mocks).
 *
 * The chip is a pure presentational toggle — wiring of view.filters happens
 * in the listview. `aria-pressed` reflects whether the "Updates" filter is
 * currently active. The accessible name includes the count so the chip is
 * meaningful when announced by a screen reader.
 *
 * See `design_handoff_email_update_flow/README.md` (Filter chip section)
 * and `OptionB.jsx` for the visual reference.
 */

/**
 * External dependencies
 */
import { Icon, starFilled } from '@wordpress/icons';
import { __, sprintf, _n } from '@wordpress/i18n';

interface UpdateAvailableChipProps {
	count: number;
	active: boolean;
	onClick: () => void;
}

export const UpdateAvailableChip = ( {
	count,
	active,
	onClick,
}: UpdateAvailableChipProps ) => {
	if ( count === 0 ) {
		return null;
	}

	const ariaLabel = sprintf(
		/* translators: %d: number of emails with an available template update */
		_n(
			'Update available, %d item',
			'Update available, %d items',
			count,
			'woocommerce'
		),
		count
	);

	const className = `woocommerce-email-listing-update-chip${
		active ? ' is-active' : ''
	}`;

	return (
		<button
			type="button"
			className={ className }
			aria-pressed={ active }
			aria-label={ ariaLabel }
			onClick={ onClick }
		>
			<Icon icon={ starFilled } size={ 16 } />
			<span className="woocommerce-email-listing-update-chip__label">
				{ __( 'Update available', 'woocommerce' ) }
			</span>
			<span className="woocommerce-email-listing-update-chip__count">
				{ count }
			</span>
		</button>
	);
};
