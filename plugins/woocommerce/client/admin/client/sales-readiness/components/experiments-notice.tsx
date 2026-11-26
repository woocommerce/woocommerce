/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
/* eslint-disable @wordpress/use-recommended-components -- The page uses these @wordpress/ui components; this chunk bundles the package. */
import { Notice } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */

/**
 * Internal dependencies
 */
import type { ExperimentsNoticeView } from '../types';
import { asLink } from './as-link';

export const EXPERIMENTS_NOTICE_PATH =
	'/wc/v3/agent-readiness/ai-assistant-experiments-notice';

/**
 * The AI shopping health notice that suggests Features for AI assistants and links to their row on
 * the Features page. Dismissing it hides it for this user.
 */
export const ExperimentsNotice = ( {
	notice,
}: {
	notice: ExperimentsNoticeView;
} ) => {
	const [ dismissed, setDismissed ] = useState( false );
	if ( dismissed ) {
		return null;
	}

	const dismiss = () => {
		setDismissed( true );
		// If this fails, the notice comes back on the next visit, which is harmless.
		apiFetch( { path: EXPERIMENTS_NOTICE_PATH, method: 'DELETE' } ).catch(
			() => undefined
		);
	};

	return (
		<Notice.Root intent="info">
			<Notice.Description>{ notice.text }</Notice.Description>
			<Notice.Actions>
				<Notice.ActionButton
					variant="outline"
					className="wc-ai-shopping__notice-action wc-sales-readiness-ui-button"
					{ ...asLink( notice.link.href ) }
				>
					{ notice.link.text }
				</Notice.ActionButton>
			</Notice.Actions>
			<Notice.CloseIcon onClick={ dismiss } />
		</Notice.Root>
	);
};
