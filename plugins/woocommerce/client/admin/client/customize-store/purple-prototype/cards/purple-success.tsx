/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, Link, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { LinkButton } from './theme-card';
import { getSiteEditorUrl } from '../urls';
import purplePreview from '../assets/purple-preview.jpg';

/**
 * Shown in place of the options right after Purple is activated from this screen.
 */
export const PurpleSuccess = ( { homeUrl }: { homeUrl: string } ) => (
	<Card.Root className="purple-cys-success">
		<Card.Content className="purple-cys-success__content">
			<img
				className="purple-cys-success__image"
				src={ purplePreview }
				alt={ __(
					'Preview of a store using the Purple theme',
					'woocommerce'
				) }
			/>
			<Text variant="heading-xl" render={ <h2 /> }>
				{ __( 'Purple is live on your store', 'woocommerce' ) }
			</Text>
			<Text className="purple-cys-success__description" render={ <p /> }>
				{ __(
					'Make it yours in the Site Editor: pick colors and fonts, then edit your homepage and store pages.',
					'woocommerce'
				) }
			</Text>
			<div className="purple-cys-success__actions">
				<LinkButton href={ getSiteEditorUrl() }>
					{ __( 'Customize in Site Editor', 'woocommerce' ) }
				</LinkButton>
				<Link href={ homeUrl } openInNewTab>
					{ __( 'View store', 'woocommerce' ) }
				</Link>
			</div>
		</Card.Content>
	</Card.Root>
);
