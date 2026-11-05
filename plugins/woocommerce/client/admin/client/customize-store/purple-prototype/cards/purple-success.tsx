/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, Text } from '@wordpress/ui';

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
			<div className="purple-cys-success__header">
				<Text variant="heading-xl" render={ <h2 /> }>
					{ __( "Purple is now your store's theme", 'woocommerce' ) }
				</Text>
				<Text
					className="purple-cys-success__description"
					render={ <p /> }
				>
					{ __(
						'Make it yours: pick colors and fonts, then edit your homepage and store pages.',
						'woocommerce'
					) }
				</Text>
			</div>
			<div className="purple-cys-success__actions">
				<LinkButton href={ getSiteEditorUrl() }>
					{ __( 'Customize in Site Editor', 'woocommerce' ) }
				</LinkButton>
				<LinkButton variant="outline" href={ homeUrl } isExternal>
					{ __( 'View store', 'woocommerce' ) }
				</LinkButton>
			</div>
		</Card.Content>
	</Card.Root>
);
