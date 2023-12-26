/**
 * External dependencies
 */
import { useUserPreferences } from '@woocommerce/data';

/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AdviceCard } from '../../../components/advice-card';
import { ShoppingBags } from '../../../images/shopping-bags';
import type { UpsellsBlockEditComponent } from './types';

export function UpsellsBlockEdit( { attributes }: UpsellsBlockEditComponent ) {
	const blockProps = useWooBlockProps( attributes );

	const isEmpty = true; // @todo: implement.

	const {
		updateUserPreferences,
		product_upsells_advice_dismissed: upsellsAdviceDismissed,
	} = useUserPreferences();

	if ( isEmpty && upsellsAdviceDismissed !== 'yes' ) {
		return (
			<div { ...blockProps }>
				<AdviceCard
					tip={ __(
						'Upsells are products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.',
						'woocommerce'
					) }
					image={ <ShoppingBags /> }
					isDismissible={ true }
					onDismiss={ () => {
						updateUserPreferences( {
							product_upsells_advice_dismissed: 'yes',
						} );
					} }
				/>
			</div>
		);
	}

	return <div { ...blockProps } />;
}
