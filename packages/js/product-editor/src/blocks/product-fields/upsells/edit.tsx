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
import { ShoppingBags } from '../../../images/shopping-bugs';
import type { UpsellsBlockEditComponent } from './types';

export function UpsellsBlockEdit( { attributes }: UpsellsBlockEditComponent ) {
	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<AdviceCard
				tip={ __(
					'Tip: Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.',
					'woocommerce'
				) }
				image={ <ShoppingBags /> }
				isDismissible={ true }
			/>
		</div>
	);
}
