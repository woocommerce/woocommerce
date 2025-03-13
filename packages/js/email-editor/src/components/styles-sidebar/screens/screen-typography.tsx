/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TypographyPanel from '../panels/typography-panel';
import ScreenHeader from './screen-header';
import { recordEventOnce } from '../../../events';

export function ScreenTypography(): JSX.Element {
	recordEventOnce( 'styles_sidebar_screen_typography_opened' );
	return (
		<>
			<ScreenHeader
				title={ __( 'Typography', 'woocommerce' ) }
				description={ __(
					'Manage the typography settings for different elements.',
					'woocommerce'
				) }
			/>
			<TypographyPanel />
		</>
	);
}
