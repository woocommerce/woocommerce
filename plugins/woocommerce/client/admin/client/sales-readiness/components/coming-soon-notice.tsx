/**
 * External dependencies
 */
/* eslint-disable @wordpress/use-recommended-components -- The design asks for these @wordpress/ui components; this chunk bundles the package. */
import { Notice } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */

/**
 * Internal dependencies
 */
import { trackReadiness, type Surface } from '../tracks';
import type { ViewLink } from '../types';
import { asLink } from './as-link';

/**
 * The warning that the store is in coming soon mode, with a link to its visibility settings.
 * AI shopping and AI shopping health show the same notice.
 */
export const ComingSoonNotice = ( {
	notice,
	surface,
}: {
	notice: { text: string; link: ViewLink };
	surface: Surface;
} ) => (
	<Notice.Root intent="warning">
		<Notice.Description>{ notice.text }</Notice.Description>
		<Notice.Actions>
			<Notice.ActionButton
				variant="outline"
				className="wc-ai-shopping__notice-action wc-sales-readiness-ui-button"
				{ ...asLink( notice.link.href, () =>
					trackReadiness( 'link_clicked', {
						surface,
						area: 'coming_soon',
					} )
				) }
			>
				{ notice.link.text }
			</Notice.ActionButton>
		</Notice.Actions>
	</Notice.Root>
);
