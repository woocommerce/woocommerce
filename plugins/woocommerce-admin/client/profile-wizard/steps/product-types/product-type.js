/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Popover, Tooltip } from '@wordpress/components';
import { useState } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { Link, Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { Icon, info } from '@wordpress/icons';

export default function ProductType( {
	annualPrice,
	description,
	isMonthlyPricing,
	label,
	moreUrl,
	slug,
} ) {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( '' );
	if ( ! annualPrice ) {
		return label;
	}

	/* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
	const toolTipText = __(
		"This product type requires a paid extension.\nWe'll add this to a cart so that\nyou can purchase and install it later.",
		'woocommerce'
	);
	/* eslint-enable @wordpress/i18n-no-collapsible-whitespace */

	return (
		<div className="woocommerce-product-type">
			<span className="woocommerce-product-type__label">{ label }</span>
			<Button
				isTertiary
				label={ __(
					'Learn more about recommended free business features',
					'woocommerce'
				) }
				onClick={ () => {
					setIsPopoverVisible( true );
				} }
			>
				<Icon icon={ info } />
			</Button>
			{ isPopoverVisible && (
				<Popover
					focusOnMount="container"
					position="top center"
					onClose={ () => setIsPopoverVisible( false ) }
				>
					{ interpolateComponents( {
						mixedString:
							description + ( moreUrl ? ' {{moreLink/}}' : '' ),
						components: {
							moreLink: moreUrl ? (
								<Link
									href={ moreUrl }
									target="_blank"
									type="external"
									onClick={ () =>
										recordEvent(
											'storeprofiler_store_product_type_learn_more',
											{
												product_type: slug,
											}
										)
									}
								>
									{ __( 'Learn more', 'woocommerce' ) }
								</Link>
							) : (
								''
							),
						},
					} ) }
				</Popover>
			) }
			<Tooltip text={ toolTipText } position="bottom center">
				<Pill>
					<span className="screen-reader-text">{ toolTipText }</span>
					{ isMonthlyPricing
						? sprintf(
								/* translators: Dollar amount (example: $4.08 ) */
								__( '$%f per month', 'woocommerce' ),
								( annualPrice / 12.0 ).toFixed( 2 )
						  )
						: sprintf(
								/* translators: Dollar amount (example: $49.00 ) */
								__( '$%f per year', 'woocommerce' ),
								annualPrice
						  ) }
				</Pill>
			</Tooltip>
		</div>
	);
}
