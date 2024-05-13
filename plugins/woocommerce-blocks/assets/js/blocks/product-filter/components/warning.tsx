/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Warning = () => {
	const isWidgetEditor = getSetting< boolean >( 'isWidgetEditor' );
	if ( isWidgetEditor ) {
		return (
			<Notice status="info" isDismissible={ false }>
				{ __(
					'The widget area containing Collection Filters block needs to be placed on a product archive page for filters to function properly.',
					'woocommerce'
				) }
			</Notice>
		);
	}

	const isSiteEditor = getSetting< boolean >( 'isSiteEditor' );
	if ( ! isSiteEditor ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'When added to a post or page, Collection Filters block needs to be nested inside a Product Collection block to function properly.',
					'woocommerce'
				) }
			</Notice>
		);
	}

	return null;
};

export default Warning;
