/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, external } from '@wordpress/icons';
import { VisuallyHidden } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';

/**
 * Show a link that displays a title, description, and optional icon. Links are opened in a new tab.
 */
const ExternalLinkCard = ( {
	href,
	title,
	description,
}: {
	href: string;
	title: string;
	description?: string;
} ): JSX.Element => {
	return (
		<a
			href={ href }
			className="wc-block-editor-components-external-link-card"
			target="_blank"
			rel="noreferrer"
		>
			<span className="wc-block-editor-components-external-link-card__content">
				<strong className="wc-block-editor-components-external-link-card__title">
					{ title }
				</strong>
				{ description && (
					<span className="wc-block-editor-components-external-link-card__description">
						{ description }
					</span>
				) }
			</span>
			<VisuallyHidden as="span">
				{
					/* translators: accessibility text */
					__( '(opens in a new tab)', 'woo-gutenberg-products-block' )
				}
			</VisuallyHidden>
			<Icon
				icon={ external }
				className="wc-block-editor-components-external-link-card__icon"
			/>
		</a>
	);
};

export default ExternalLinkCard;
