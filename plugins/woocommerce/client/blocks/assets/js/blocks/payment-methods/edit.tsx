/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { getPaymentMethods } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import './style.scss';
import { getPaymentMethodIcons } from './getPaymentMethodIcons';

const Edit = () => {
	const paymentMethods = getPaymentMethods();
	const blockProps = useBlockProps();
	const paymentMethodIcons = getPaymentMethodIcons( paymentMethods );

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-payment-methods">
				{ Object.keys( paymentMethods ).length === 0 ? (
					<p>
						<small>
							{ __(
								'No payment methods are currently active.',
								'woocommerce'
							) }
						</small>
					</p>
				) : (
					<div className="wc-block-payment-methods__content">
						<ul className="wc-block-payment-methods__list">
							{ paymentMethodIcons.map( ( icon ) => {
								const src =
									typeof icon === 'string' ? icon : icon.src;
								const alt =
									typeof icon === 'string' ? '' : icon.alt;
								const id =
									typeof icon === 'string' ? icon : icon.id;
								return (
									<li key={ id }>
										<img
											src={ src || '' }
											alt={ alt || '' }
										/>
									</li>
								);
							} ) }
						</ul>
					</div>
				) }
			</div>
		</div>
	);
};

export default Edit;
