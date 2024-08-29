/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Link } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import {
	createElement,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { useInstanceId } from '@wordpress/compose';
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
import { useValidation } from '../../../contexts/validation-context';
import { InventoryEmailBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< InventoryEmailBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const notifyLowStockAmount = getSetting( 'notifyLowStockAmount', 2 );

	const [ lowStockAmount, setLowStockAmount ] = useEntityProp< number >(
		'postType',
		'product',
		'low_stock_amount'
	);

	const id = useInstanceId( BaseControl, 'low_stock_amount' ) as string;

	const {
		ref: lowStockAmountRef,
		error: lowStockAmountValidationError,
		validate: validateLowStockAmount,
	} = useValidation< Product >(
		`low_stock_amount-${ clientId }`,
		async function stockQuantityValidator() {
			if ( lowStockAmount && lowStockAmount < 0 ) {
				return {
					message: __(
						'This field must be a positive number.',
						'woocommerce'
					),
				};
			}
		},
		[ lowStockAmount ]
	);

	return (
		<>
			<div { ...blockProps }>
				<div className="wp-block-columns">
					<div className="wp-block-column">
						<BaseControl
							id={ id }
							label={ __(
								'Email me when stock reaches',
								'woocommerce'
							) }
							help={
								lowStockAmountValidationError ||
								createInterpolateElement(
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
								)
							}
							className={
								lowStockAmountValidationError && 'has-error'
							}
						>
							<InputControl
								id={ id }
								ref={ lowStockAmountRef }
								name={ 'low_stock_amount' }
								placeholder={ sprintf(
									// translators: Default quantity to notify merchants of low stock.
									__( '%d (store default)', 'woocommerce' ),
									notifyLowStockAmount
								) }
								onChange={ setLowStockAmount }
								onBlur={ validateLowStockAmount }
								value={ lowStockAmount }
								type="number"
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
