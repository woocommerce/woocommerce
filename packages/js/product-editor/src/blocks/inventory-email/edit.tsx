/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';

import {
	createElement,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

import { useBlockProps } from '@wordpress/block-editor';

import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

export function Edit() {
	const blockProps = useBlockProps();
	const notifyLowStockAmount = getSetting( 'notifyLowStockAmount', 2 );

	const [ lowStockAmount, setLowStockAmount ] = useEntityProp(
		'postType',
		'product',
		'low_stock_amount'
	);

	return (
		<>
			<div { ...blockProps }>
				<div className="wp-block-columns">
					<div className="wp-block-column">
						<BaseControl
							id={ 'product_inventory_email' }
							label={ __(
								'Email me when stock reaches',
								'woocommerce'
							) }
							help={ createInterpolateElement(
								__(
									'Make sure to enable notifications in <link>store settings.</link>',
									'woocommerce'
								),
								{
									link: (
										<Link
											href={ `${ getSetting(
												'adminUrl'
											) }admin.php?page=wc-settings&tab=products&section=inventory` }
											target="_blank"
											type="external"
										></Link>
									),
								}
							) }
						>
							<InputControl
								name={ 'woocommerce-product-name' }
								placeholder={ sprintf(
									// translators: Default quantity to notify merchants of low stock.
									__( '%d (store default)', 'woocommerce' ),
									notifyLowStockAmount
								) }
								onChange={ setLowStockAmount }
								value={ lowStockAmount }
								min={ 0 }
							/>
						</BaseControl>
					</div>
					<div className="wp-block-column"></div>
				</div>
			</div>
		</>
	);
}
