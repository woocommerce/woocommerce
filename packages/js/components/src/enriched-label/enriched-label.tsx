/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { Icon, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Link from '../link';

type EnrichedLabelProps = {
	helpDescription: string;
	label: string;
	moreUrl: string;
	tooltipLinkCallback: () => void;
};

export const EnrichedLabel: React.FC< EnrichedLabelProps > = ( {
	helpDescription,
	label,
	moreUrl,
	tooltipLinkCallback,
} ) => {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	return (
		<>
			<span className="woocommerce-enriched-label__text">{ label }</span>
			{ helpDescription && (
				<div
					className="woocommerce-enriched-label__help-wrapper"
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
											onClick={ tooltipLinkCallback }
										>
											{ __(
												'Learn more',
												'woocommerce'
											) }
										</Link>
									) : (
										<div />
									),
								},
							} ) }
						</Popover>
					) }
				</div>
			) }
		</>
	);
};
