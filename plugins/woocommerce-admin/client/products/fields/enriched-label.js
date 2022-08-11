/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { useState } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { Icon, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './enriched-label.scss';

export default function EnrichedLabel( {
	helpDescription,
	label,
	moreUrl,
	slug,
} ) {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( '' );

	return (
		<>
			<span className="woocommerce-add-product__enriched-label">
				{ label }
			</span>
			{ helpDescription && (
				<div
					className="woocommerce-add-product__help-wrapper"
					onMouseLeave={ () => setIsPopoverVisible( false ) }
				>
					<Button
						label={ __( 'Help button', 'woocommerce' ) }
						onMouseEnter={ () => setIsPopoverVisible( true ) }
					>
						<Icon icon={ help } />
					</Button>

					{ isPopoverVisible && (
						<Popover focusOnMount="container" position="top center">
							{ interpolateComponents( {
								mixedString:
									helpDescription +
									( moreUrl ? ' {{moreLink/}}' : '' ),
								components: {
									moreLink: moreUrl ? (
										<Link
											href={ moreUrl }
											target="_blank"
											type="external"
											onClick={ () =>
												recordEvent(
													'add_product_learn_more',
													{
														category: slug,
													}
												)
											}
										>
											{ __(
												'Learn more',
												'woocommerce'
											) }
										</Link>
									) : (
										''
									),
								},
							} ) }
						</Popover>
					) }
				</div>
			) }
		</>
	);
}
