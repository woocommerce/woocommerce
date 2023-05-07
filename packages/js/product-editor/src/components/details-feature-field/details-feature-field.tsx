/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	useFormContext,
	Link,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	createElement,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../../utils';
import { PRODUCT_DETAILS_SLUG } from '../../constants';

export const DetailsFeatureField = () => {
	const { getCheckboxControlProps } = useFormContext< Product >();

	return (
		<CheckboxControl
			label={
				<>
					{ __( 'Feature this product', 'woocommerce' ) }
					<Tooltip
						text={ createInterpolateElement(
							__(
								'Include this product in a featured section on your website with a widget or shortcode. <moreLink />',
								'woocommerce'
							),
							{
								moreLink: (
									<Link
										href="https://woocommerce.com/document/woocommerce-shortcodes/#products"
										target="_blank"
										type="external"
										onClick={ () =>
											recordEvent(
												'add_product_learn_more',
												{
													category:
														PRODUCT_DETAILS_SLUG,
												}
											)
										}
									>
										{ __( 'Learn more', 'woocommerce' ) }
									</Link>
								),
							}
						) }
					/>
				</>
			}
			{ ...getCheckboxControlProps(
				'featured',
				getCheckboxTracks( 'featured' )
			) }
		/>
	);
};
