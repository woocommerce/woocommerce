/**
 * External dependencies
 */
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { registerBlockVariation } from '@wordpress/blocks';
import type { Block, InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Add support for top social networks
const supportedVariations = [
	// 'behance',
	// 'bluesky',
	// 'chain', // Link
	// 'discord',
	'facebook',
	'feed',
	'github',
	// 'gravatar',
	'instagram',
	'linkedin',
	'mail',
	'mastodon',
	// 'medium',
	// 'patreon',
	// 'pinterest',
	// 'reddit',
	// 'snapchat',
	// 'soundcloud',
	// 'spotify',
	// 'telegram',
	// 'threads',
	// 'tiktok',
	// 'tumblr',
	// 'twitch',
	'twitter',
	// 'vimeo',
	// 'vk',
	'wordpress',
	// 'whatsapp',
	'x',
	// 'youtube',
	// 'yelp',
];

function unregisterBlockVariations() {
	// Remove unsupported social links
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/disable-social-link-variations',
		( settings, name ) => {
			if ( name === 'core/social-link' ) {
				// eslint-disable-next-line @typescript-eslint/no-unsafe-return
				return {
					...settings,
					variations: settings.variations.filter( ( variation ) =>
						supportedVariations.includes( variation.name )
					),
					supports: {
						...settings.supports,
						layout: false,
					},
				};
			}
			// eslint-disable-next-line @typescript-eslint/no-unsafe-return
			return settings;
		}
	);
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
				service: 'twitter',
				url: 'https://twitter.com/WordPress',
			},
		},
	];

	registerBlockVariation( 'core/social-links', {
		name: 'social-links-default',
		title: 'Social Icons',
		attributes: {
			openInNewTab: true,
			showLabels: false,
			iconBackgroundColorValue: '#720eec', // woocommerce primary color
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
		.block-editor-panel-color-gradient-settings__dropdown:has([title="${ labelText }"]) {
			display: none !important;
		}
		`;

		// eslint-disable-next-line @wordpress/i18n-text-domain -- using core label.
		const blockControlsLabelText = __( 'Size' );
		const customControlsCss = `
		.components-toolbar-group:has([aria-label="${ blockControlsLabelText }"]) {
			display: none !important;
		}`;

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls group="color">
					<style>{ customCss }</style>
				</InspectorControls>
				<BlockControls group="other">
					<style>{ customControlsCss }</style>
				</BlockControls>
			</>
		);
	};

function removeSocialLinksIconColor(): void {
	addFilter(
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
