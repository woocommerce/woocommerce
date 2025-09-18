/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import type {
	Block,
	BlockConfiguration,
	InnerBlockTemplate,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';
import { registerBlockVariationForEmail } from '../../config-tools';
import { addFilterForEmail } from '../../config-tools/filters';

// Add support for top social networks
const supportedVariations = [
	'behance',
	'bluesky',
	'chain', // Link
	'discord',
	'facebook',
	'feed',
	'github',
	'gravatar',
	'instagram',
	'linkedin',
	'mail',
	'mastodon',
	'medium',
	'patreon',
	'pinterest',
	'reddit',
	'spotify',
	'telegram',
	'threads',
	'tiktok',
	'tumblr',
	'twitch',
	'twitter',
	'vimeo',
	'wordpress',
	'whatsapp',
	'x',
	'youtube',
];

function unregisterBlockVariations() {
	// Remove unsupported social links and disable layout support
	updateBlockSettings( 'core/social-link', ( current ) => ( {
		...current,
		variations:
			// @ts-expect-error Type BlockConfiguration is missing variations.
			( ( current as BlockConfiguration ).variations || [] ).filter(
				( variation: { name: string } ) =>
					supportedVariations.includes( variation.name )
			),
		supports: {
			...current.supports,
			layout: false,
		},
	} ) );
}

function registerCustomSocialLinksBlockVariation() {
	// Register a custom variation for the social links block
	// This variation is used to display the social links in the email editor by automatically adding some preset social links
	const socialLinksVariations: InnerBlockTemplate[] = [
		{
			// @ts-expect-error Type not complete.
			name: 'core/social-link',
			attributes: {
				service: 'wordpress',
				url: 'https://wordpress.org',
			},
		},
		{
			// @ts-expect-error Type not complete.
			name: 'core/social-link',
			attributes: {
				service: 'facebook',
				url: 'https://www.facebook.com/WordPress/',
			},
		},
		{
			// @ts-expect-error Type not complete.
			name: 'core/social-link',
			attributes: {
				service: 'x',
				url: 'https://x.com/WordPress',
			},
		},
	];

	registerBlockVariationForEmail( 'core/social-links', {
		name: 'social-links-default',
		title: 'Social Icons',
		attributes: {
			openInNewTab: true,
			showLabels: false,
			align: 'center',
			className: 'is-style-logos-only', // use logo-only style as the default because it's the most common use case and it looks nice on all email clients.
		},
		isDefault: true, // set this as the default variation
		innerBlocks: socialLinksVariations,
	} );
}

const disableIconColor =
	( BlockEdit: React.ElementType ) => ( props: Block< unknown > ) => {
		if ( props.name !== 'core/social-links' ) {
			return <BlockEdit { ...props } />;
		}
		// we are doing this because we don't want to show the icon color picker in the social links block (we can't change png image color)
		// and there isn't a great way to remove the icon color from the core block attributes
		// eslint-disable-next-line @wordpress/i18n-text-domain -- using core label.
		const labelText = __( 'Icon color' );
		const customCss = `
		.block-editor-tools-panel-color-gradient-settings__item:has([title="${ labelText }"]) {
			display: none !important;
		}
		.block-editor-tools-panel-color-gradient-settings__item:nth-child(2 of .block-editor-tools-panel-color-gradient-settings__item){
			border-top:1px solid #ddd;
			border-top-left-radius:2px;
			border-top-right-radius:2px;
		}
		`;

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls group="color">
					<style>{ customCss }</style>
				</InspectorControls>
			</>
		);
	};

function removeSocialLinksIconColor(): void {
	addFilterForEmail(
		'editor.BlockEdit',
		'woocommerce-email-editor/disable-social-links-icon-color',
		disableIconColor
	);
}

/**
 * Enhances the social links and social link blocks
 */
function enhanceSocialLinksBlock() {
	unregisterBlockVariations();
	registerCustomSocialLinksBlockVariation();
	removeSocialLinksIconColor();
}

export { enhanceSocialLinksBlock };
