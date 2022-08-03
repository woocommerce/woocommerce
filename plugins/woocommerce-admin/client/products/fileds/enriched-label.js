/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { Fragment, useState } from '@wordpress/element';
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
		<Fragment>
			<span className="woocommerce-add-product__enriched-label">
				{ label }
			</span>
			{ helpDescription && (
				<Button
					label={ __( 'Help button', 'woocommerce' ) }
					onClick={ () => {
						setIsPopoverVisible( true );
					} }
				>
					<Icon icon={ help } />
					{ isPopoverVisible && (
						<Popover
							focusOnMount="container"
							position="top center"
							onClose={ () => setIsPopoverVisible( false ) }
						>
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
				</Button>
			) }
		</Fragment>
	);
}
