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
import type { CrossSellsBlockEditComponent } from './types';
import { CashRegister } from '../../../images/cash-register';

export function CrossSellsBlockEdit( {
	attributes,
}: CrossSellsBlockEditComponent ) {
	const blockProps = useWooBlockProps( attributes );

	const isEmpty = true; // @todo: implement.

	if ( isEmpty ) {
		return (
			<div { ...blockProps }>
				<AdviceCard
					tip={ __(
						'Tip: By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value.',
						'woocommerce'
					) }
					image={ <CashRegister /> }
					isDismissible={ true }
				/>
			</div>
		);
	}

	return <div { ...blockProps } />;
}
