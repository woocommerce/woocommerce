/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import { formatPrice } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-totals',
	} );

	const {
		borderBottomColor,
		borderLeftColor,
		borderRightColor,
		borderTopColor,
		borderWidth,
	} = blockProps.style;

	const borderStyles = {
		borderBottomColor,
		borderLeftColor,
		borderRightColor,
		borderTopColor,
		borderWidth,
	} as React.CSSProperties;

	return (
		<div { ...blockProps }>
			<Disabled>
				<table
					style={ borderStyles }
					cellSpacing="0"
					className="wc-block-order-confirmation-totals__table"
				>
					<thead>
						<tr>
							<th className="wc-block-order-confirmation-totals__product">
								{ __( 'Product', 'woocommerce' ) }
							</th>
							<th className="wc-block-order-confirmation-totals__total">
								{ __( 'Total', 'woocommerce' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						<tr className="woocommerce-table__line-item order_item">
							<th
								scope="row"
								className="wc-block-order-confirmation-totals__product"
							>
								<a href="#link">
									{ _x(
										'Test Product',
										'sample product name',
										'woocommerce'
									) }
								</a>
								&nbsp;
								<strong className="product-quantity">
									&times;&nbsp;2
								</strong>
							</th>
							<td className="wc-block-order-confirmation-totals__total">
								{ formatPrice( 2000 ) }
							</td>
						</tr>
						<tr className="woocommerce-table__line-item order_item">
							<th
								scope="row"
								className="wc-block-order-confirmation-totals__product"
							>
								<a href="#link">
									{ _x(
										'Test Product',
										'sample product name',
										'woocommerce'
									) }
								</a>
								&nbsp;
								<strong className="product-quantity">
									&times;&nbsp;2
								</strong>
							</th>
							<td className="wc-block-order-confirmation-totals__total">
								{ formatPrice( 2000 ) }
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<th
								className="wc-block-order-confirmation-totals__label"
								scope="row"
							>
								{ __( 'Total', 'woocommerce' ) }
							</th>
							<td className="wc-block-order-confirmation-totals__total">
								{ formatPrice( 4000 ) }
							</td>
						</tr>
					</tfoot>
				</table>
			</Disabled>
		</div>
	);
};

export default Edit;
