/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
/* eslint-disable @wordpress/use-recommended-components -- Matches the notices on AI tools; this chunk bundles the package. */
import { Link, Notice } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */

export type AiAccessNoticeProps = {
	intent: 'warning' | 'info';
	/**
	 * The notice text. An <a>…</a> pair in it becomes a link to `href`.
	 */
	message: string;
	href?: string;
};

const AiAccessNotice = ( { intent, message, href }: AiAccessNoticeProps ) => (
	<Notice.Root
		intent={ intent }
		className="woocommerce-ai-access-settings__notice"
	>
		<Notice.Description>
			{ href
				? createInterpolateElement( message, {
						a: <Link href={ href } />,
					} )
				: message }
		</Notice.Description>
	</Notice.Root>
);

export default AiAccessNotice;
