/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { close } from '@wordpress/icons';
import { useInstanceId } from '@wordpress/compose';
import { useUserPreferences } from '@woocommerce/data';
import classNames from 'classnames';

export interface AdviceCardProps {
	tip?: string;
	isDismissible?: boolean;
	onDismiss?: () => void;
	image?: React.ReactNode;
}

export const AdviceCard: React.FC< AdviceCardProps > = ( {
	tip,
	image = null,
	isDismissible = true,
	onDismiss,
} ) => {
	// Generate a unique ID for the advice card.
	const adviceCardId = useInstanceId(
		AdviceCard,
		'advice-card-instance'
	) as string;

	const { updateUserPreferences, product_advice_card_dismissed } =
		useUserPreferences();

	if ( ! onDismiss ) {
		onDismiss = () => {
			updateUserPreferences( {
				product_advice_card_dismissed: {
					...product_advice_card_dismissed,
					[ adviceCardId ]: 'yes',
				},
			} );
		};
	}

	// Check if the advice card has been dismissed.
	if (
		product_advice_card_dismissed &&
		product_advice_card_dismissed?.[ adviceCardId ] === 'yes'
	) {
		return null;
	}

	return (
		<div
			className={ classNames( 'woocommerce-advice-card', {
				'is-dismissible': isDismissible,
			} ) }
			role="group"
		>
			{ isDismissible && (
				<div className="woocommerce-advice-card__header">
					<Button
						className="woocommerce-advice-card__dismiss-button"
						onClick={ onDismiss }
						icon={ close }
						label={ __( 'Dismiss', 'woocommerce' ) }
						isSmall={ true }
					/>
				</div>
			) }
			<div className="woocommerce-advice-card__body">{ image }</div>
			{ tip && tip.length > 0 && (
				<div className="woocommerce-advice-card__footer">{ tip }</div>
			) }
		</div>
	);
};
