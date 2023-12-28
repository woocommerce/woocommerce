/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { close } from '@wordpress/icons';
import { useInstanceId } from '@wordpress/compose';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AdviceCardProps } from './types';

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
		<div className="woocommerce-advice-card">
			<div className="woocommerce-advice-card__body">
				{ isDismissible && (
					<Button
						className="woocommerce-advice-card__dismiss-button"
						onClick={ onDismiss }
						icon={ close }
						label={ __( 'Dismiss', 'woocommerce' ) }
					/>
				) }

				{ image && (
					<div className="woocommerce-advice-card__image">
						{ image }
					</div>
				) }

				{ Boolean( tip?.length ) && (
					<p className="woocommerce-advice-card__tip">{ tip }</p>
				) }
			</div>
		</div>
	);
};
