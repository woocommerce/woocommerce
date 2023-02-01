/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	useFormContext2,
	Link,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Controller } from 'react-hook-form';
import _omit from 'lodash/omit';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../../sections/utils';
import { PRODUCT_DETAILS_SLUG } from '../constants';

export const DetailsFeatureField = () => {
	const { control } = useFormContext2< Product >();

	const checkboxTracksFn = getCheckboxTracks( 'featured' );

	return (
		<Controller
			name="featured"
			control={ control }
			render={ ( { field } ) => (
				<CheckboxControl
					{ ..._omit( field, [ 'value' ] ) }
					onChange={ ( value ) => {
						field.onChange( value );
						checkboxTracksFn.onChange( value );
					} }
					label={
						<>
							{ __( 'Feature this product', 'woocommerce' ) }
							<Tooltip
								text={ interpolateComponents( {
									mixedString: __(
										'Include this product in a featured section on your website with a widget or shortcode. {{moreLink/}}',
										'woocommerce'
									),
									components: {
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
												{ __(
													'Learn more',
													'woocommerce'
												) }
											</Link>
										),
									},
								} ) }
							/>
						</>
					}
				/>
			) }
		></Controller>
	);
};
