/**
 * External dependencies
 */
import { Text } from '@woocommerce/experimental';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent, ExtraProperties } from '@woocommerce/tracks';
import { __ } from '@wordpress/i18n';

interface TextProps {
	/**
	 * HTML element to use for the Text component. Uses `span` by default.
	 */
	as?: string;
	className?: string;
}

interface TrackedLinkProps {
	textProps?: TextProps;
	/**
	 * The complete translatable string that includes {{Link}} and {{/Link}} placeholders
	 * Example: "Visit the {{Link}}Official WooCommerce Marketplace{{/Link}} to find more tax solutions"
	 */
	message: string;
	eventName?: string;
	eventProperties?: ExtraProperties;
	targetUrl: string;
	linkType?: 'wc-admin' | 'wp-admin' | 'external';
	target?: '_blank' | undefined;
	/**
	 * Optional callback function to be called when the link is clicked
	 * If provided, this will be called instead of the default recordEvent behavior
	 */
	onClickCallback?: () => void;
}

/**
 * A component that renders a link with tracking capabilities.
 */
export const TrackedLink = ( {
	textProps,
	message,
	eventName = '',
	eventProperties = {},
	targetUrl,
	linkType = 'wc-admin',
	target,
	onClickCallback,
}: TrackedLinkProps ) => {
	const linkTextMatch = message.match( /{{Link}}(.*?){{\/Link}}/ );
	const linkText = linkTextMatch ? linkTextMatch[ 1 ] : '';
	const shouldOpenInNewTab = linkType === 'external' && target === '_blank';

	return (
		<Text { ...textProps }>
			{ interpolateComponents( {
				mixedString: message,
				components: {
					Link: (
						<Link
							onClick={ () => {
								if ( onClickCallback ) {
									onClickCallback();
								} else {
									recordEvent( eventName, eventProperties );
								}
								if ( linkType !== 'external' ) {
									window.location.href = targetUrl;
									return false;
								}
							} }
							href={ targetUrl }
							type={ linkType }
							target={ shouldOpenInNewTab ? '_blank' : undefined }
							aria-label={
								shouldOpenInNewTab
									? `${ linkText } (${ __(
											'opens in a new tab',
											'woocommerce'
									  ) })`
									: undefined
							}
						/>
					),
				},
			} ) }
		</Text>
	);
};
