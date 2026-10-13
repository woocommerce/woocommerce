/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Badge, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { ThemeCard, LinkButton } from './theme-card';
import { getCurrentThemeEditorUrl } from '../urls';
import type { PurplePrototypeSettings } from '../settings';

type CurrentThemeCardProps = {
	theme: PurplePrototypeSettings[ 'activeTheme' ];
};

export const CurrentThemeCard = ( { theme }: CurrentThemeCardProps ) => (
	<ThemeCard
		className="purple-cys-theme-card--current-theme"
		media={
			theme.screenshot ? <img src={ theme.screenshot } alt="" /> : null
		}
	>
		<Badge>{ __( 'Current theme', 'woocommerce' ) }</Badge>
		<Text variant="heading-lg" render={ <h2 /> }>
			{ sprintf(
				/* translators: %s: name of the active theme, for example "Twenty Twenty-Four". */
				__( 'Keep %s', 'woocommerce' ),
				theme.name
			) }
		</Text>
		<Text className="purple-cys-theme-card__description" render={ <p /> }>
			{ __(
				'Stay with the theme you have and edit it in the Site Editor.',
				'woocommerce'
			) }
		</Text>
		<div className="purple-cys-theme-card__actions">
			<LinkButton
				variant="outline"
				tone="neutral"
				href={ getCurrentThemeEditorUrl( theme.isBlockTheme ) }
			>
				{ __( 'Open Site Editor', 'woocommerce' ) }
			</LinkButton>
		</div>
	</ThemeCard>
);
