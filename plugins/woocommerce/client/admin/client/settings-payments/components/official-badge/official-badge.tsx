/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Popover } from '@wordpress/components';
import { Link, Pill } from '@woocommerce/components';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

interface OfficialBadgeProps {
	/**
	 * The style of the badge.
	 */
	variant: 'expanded' | 'compact';
}

/**
 * A component that displays an official badge.
 * The style of the badge can be either "expanded" or "compact".
 *
 * @example
 * // Render an official badge with icon and text.
 * <OfficialBadge variant="expanded" />
 *
 * @example
 * // Render an official badge with just the icon.
 * <OfficialBadge variant="compact" />
 */
export const OfficialBadge = ( { variant }: OfficialBadgeProps ) => {
	const [ isPopoverVisible, setPopoverVisible ] = useState( false );

	const togglePopover = () => {
		setPopoverVisible( ! isPopoverVisible );
	};

	return (
		<Pill className={ `woocommerce-official-extension-badge` }>
			<span
				className="woocommerce-official-extension-badge__container"
				tabIndex={ 0 }
				role="button"
				onClick={ togglePopover }
				onKeyDown={ ( event ) => {
					if ( event.key === 'Enter' || event.key === ' ' ) {
						togglePopover();
					}
				} }
			>
				<img
					src={ WC_ASSET_URL + 'images/icons/official-extension.svg' }
					alt={ __(
						'Official WooCommerce extension badge',
						'woocommerce'
					) }
				/>
				{ variant === 'expanded' && (
					<span>{ __( 'Official', 'woocommerce' ) }</span>
				) }
				{ isPopoverVisible && (
					<Popover
						className="woocommerce-official-extension-badge-popover"
						placement="top-start"
						offset={ 4 }
						variant="unstyled"
						focusOnMount={ true }
						noArrow={ true }
						shift={ true }
						onClose={ () => setPopoverVisible( false ) }
					>
						<div className="components-popover__content-container">
							<p>
								{ createInterpolateElement(
									__(
										'This is an Official WooCommerce payment extension. <learnMoreLink />',
										'woocommerce'
									),
									{
										learnMoreLink: (
											<Link
												href="https://woocommerce.com/learn-more-about-official-partner-badging/"
												target="_blank"
												rel="noreferrer"
												type="external"
											>
												{ __(
													'Learn more',
													'woocommerce'
												) }
											</Link>
										),
									}
								) }
							</p>
						</div>
					</Popover>
				) }
			</span>
		</Pill>
	);
};
