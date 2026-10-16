/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import type { BlockConfiguration, BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';
import { addFilterForEmail } from '../../config-tools/filters';

// Providers the email renderer can render as a clickable video thumbnail.
// Keep in sync with VIDEO_PROVIDERS in
// packages/php/email-editor/src/Integrations/Core/Renderer/Blocks/class-embed.php.
// The renderer's AUDIO_PROVIDERS are deliberately not offered in the editor:
// they render as a bare "Listen on …" link button, which users can build
// better themselves with core blocks. The renderer keeps supporting them for
// stored content, so audio URLs entered manually get the warning notice.
const supportedProviderDomains: Record< string, string[] > = {
	youtube: [ 'youtube.com', 'youtu.be' ],
	videopress: [ 'videopress.com', 'video.wordpress.com' ],
	vimeo: [ 'vimeo.com', 'player.vimeo.com' ],
	tiktok: [ 'tiktok.com' ],
	dailymotion: [ 'dailymotion.com', 'dai.ly' ],
};

export function isSupportedProviderUrl( url: string ): boolean {
	let hostname = '';
	try {
		hostname = new URL( url ).hostname;
	} catch {
		return false;
	}
	return Object.values( supportedProviderDomains ).some( ( domains ) =>
		domains.some(
			( domain ) =>
				hostname === domain || hostname.endsWith( `.${ domain }` )
		)
	);
}

interface EmbedAttributes {
	url?: string;
	type?: string;
	responsive?: boolean;
	[ key: string ]: unknown;
}

const withEmailEmbedAdjustments = createHigherOrderComponent(
	( BlockEdit ) =>
		function EmailEmbedAdjustments(
			props: BlockEditProps< EmbedAttributes > & { name: string }
		) {
			const isEmbed = props.name === 'core/embed';
			const { responsive } = props.attributes;
			const { setAttributes } = props;
			const blockEditorDispatch = useDispatch( 'core/block-editor' );
			// Reset the responsive attribute on embeds coming from stored or
			// pasted content (see removeUnsupportedVariations). The change is
			// marked non-persistent so it neither dirties a freshly opened
			// email nor creates an undo step the user gets stuck on.
			useEffect( () => {
				if ( isEmbed && responsive ) {
					// @ts-expect-error Unstable action is missing from the dispatch types.
					blockEditorDispatch.__unstableMarkNextChangeAsNotPersistent();
					setAttributes( { responsive: false } );
				}
			}, [ isEmbed, responsive, setAttributes, blockEditorDispatch ] );

			if ( ! isEmbed ) {
				return <BlockEdit { ...props } />;
			}
			const { url, type } = props.attributes;
			// WordPress embeds are rendered as a rich link card in the email, so no warning is needed.
			if (
				! url ||
				type === 'wp-embed' ||
				isSupportedProviderUrl( url )
			) {
				return <BlockEdit { ...props } />;
			}
			return (
				<>
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'This embed is not supported in emails. It will be sent as a link.',
							__i18n_text_domain__
						) }
					</Notice>
					<BlockEdit { ...props } />
				</>
			);
		},
	'withEmailEmbedAdjustments'
);

// The `wordpress` variation is kept although it has no domain entry: WordPress
// embeds are detected via the `wp-embed` type attribute and rendered as a rich
// link card in the email.
const keptVariations = [
	...Object.keys( supportedProviderDomains ),
	'wordpress',
];

function removeUnsupportedVariations() {
	updateBlockSettings( 'core/embed', ( current ) => ( {
		...current,
		variations:
			// @ts-expect-error Type BlockConfiguration is missing variations.
			( ( current as BlockConfiguration ).variations || [] )
				.filter( ( variation: { name: string } ) =>
					keptVariations.includes( variation.name )
				)
				// Drop the responsive attribute: responsive iframe sizing has
				// no effect in emails, and a truthy value would only surface a
				// "Media settings" panel with a resize toggle that does nothing.
				.map(
					( variation: {
						attributes?: Record< string, unknown >;
					} ) => {
						const { responsive, ...attributes } =
							variation.attributes || {};
						return { ...variation, attributes };
					}
				),
	} ) );
}

function addEmailEmbedAdjustments() {
	addFilterForEmail(
		'editor.BlockEdit',
		'woocommerce-email-editor/embed-adjustments',
		withEmailEmbedAdjustments
	);
}

/**
 * Enhances the embed block for the email editor: only the video providers the
 * email renderer can render as clickable thumbnails (plus the WordPress embed,
 * rendered as a rich link card) are offered in the inserter, without the
 * responsive behavior that has no effect in emails; embeds that would be sent
 * as a link show a warning in the editor.
 */
function enhanceEmbedBlock() {
	removeUnsupportedVariations();
	addEmailEmbedAdjustments();
}

export { enhanceEmbedBlock };
