/**
 * External dependencies
 */
import {
	Link,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { format as formatDate } from '@wordpress/date';
import {
	createElement,
	createInterpolateElement,
	Fragment,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRODUCT_SCHEDULED_SALE_SLUG } from '../../../constants';
import { ScheduleSaleLabelProps } from './types';

export function ScheduleSaleLabel( {}: ScheduleSaleLabelProps ) {
	const timeFormat = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return ( getOption( 'time_format' ) as string ) || 'H:i';
	} );

	return (
		<>
			{ __( 'Schedule sale', 'woocommerce' ) }
			<Tooltip
				text={ createInterpolateElement(
					__(
						'The sale will start at the beginning of the "From" date (<StartTime />) and expire at the end of the "To" date (<EndTime />). <MoreLink />',
						'woocommerce'
					),
					{
						StartTime: (
							<span>
								{ formatDate(
									timeFormat,
									// @ts-expect-error TODO - fix this type error with moment
									moment().startOf( 'day' )
								) }
							</span>
						),
						EndTime: (
							<span>
								{ formatDate(
									timeFormat,
									// @ts-expect-error TODO - fix this type error with moment
									moment().endOf( 'day' )
								) }
							</span>
						),
						MoreLink: (
							<Link
								href="https://woocommerce.com/document/managing-products/#product-data"
								target="_blank"
								type="external"
								onClick={ () =>
									recordEvent( 'add_product_learn_more', {
										category: PRODUCT_SCHEDULED_SALE_SLUG,
									} )
								}
							>
								{ __( 'Learn more', 'woocommerce' ) }
							</Link>
						),
					}
				) }
			/>
		</>
	);
}
