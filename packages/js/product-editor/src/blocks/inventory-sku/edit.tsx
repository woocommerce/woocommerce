/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	createInterpolateElement,
	Fragment,
} from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';
import { Icon, help } from '@wordpress/icons';

import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */

export function Edit() {
	const blockProps = useBlockProps();

	const [ sku, setSku ] = useEntityProp( 'postType', 'product', 'sku' );

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ 'product_sku' }
				className="woocommerce-product-form_inventory-sku"
				label={ createInterpolateElement(
					__( 'Sku <description />', 'woocommerce' ),
					{
						description: (
							<>
								<span className="woocommerce-product-form__optional-input">
									{ __(
										'(STOCK KEEPING UNIT)',
										'woocommerce'
									) }
								</span>
								<Tooltip
									text={ __(
										'This is placeholder text for the SKU field.',
										'woocommerce'
									) }
								>
									<Icon
										icon={ help }
										size={ 20 }
										color="#949494"
									/>
								</Tooltip>
							</>
						),
					}
				) }
			>
				<InputControl
					name={ 'woocommerce-product-name' }
					placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
					onChange={ setSku }
					value={ sku || '' }
				/>
			</BaseControl>
		</div>
	);
}
