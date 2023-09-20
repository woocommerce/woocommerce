/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { formatPrice } from '@woocommerce/price-format';
import { date } from '@wordpress/date';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-summary',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<ul className="wc-block-order-confirmation-summary-list">
					<li className="wc-block-order-confirmation-summary-list-item">
						<span className="wc-block-order-confirmation-summary-list-item__key">
							{ __(
								'Order number:',
								'woo-gutenberg-products-block'
							) }
						</span>{ ' ' }
						<span className="wc-block-order-confirmation-summary-list-item__value">
							123
						</span>
					</li>
					<li className="wc-block-order-confirmation-summary-list-item">
						<span className="wc-block-order-confirmation-summary-list-item__key">
							{ __( 'Date:', 'woo-gutenberg-products-block' ) }
						</span>{ ' ' }
						<span className="wc-block-order-confirmation-summary-list-item__value">
							{ date(
								getSetting( 'dateFormat' ),
								new Date(),
								undefined
							) }
						</span>
					</li>
					<li className="wc-block-order-confirmation-summary-list-item">
						<span className="wc-block-order-confirmation-summary-list-item__key">
							{ __( 'Total:', 'woo-gutenberg-products-block' ) }
						</span>{ ' ' }
						<span className="wc-block-order-confirmation-summary-list-item__value">
							{ formatPrice( 4000 ) }
						</span>
					</li>
					<li className="wc-block-order-confirmation-summary-list-item">
						<span className="wc-block-order-confirmation-summary-list-item__key">
							{ __( 'Email:', 'woo-gutenberg-products-block' ) }
						</span>{ ' ' }
						<span className="wc-block-order-confirmation-summary-list-item__value">
							test@test.com
						</span>
					</li>
					<li className="wc-block-order-confirmation-summary-list-item">
						<span className="wc-block-order-confirmation-summary-list-item__key">
							{ __(
								'Payment method:',
								'woo-gutenberg-products-block'
							) }
						</span>{ ' ' }
						<span className="wc-block-order-confirmation-summary-list-item__value">
							{ __(
								'Credit Card',
								'woo-gutenberg-products-block'
							) }
						</span>
					</li>
				</ul>
			</Disabled>
		</div>
	);
};

export default Edit;
