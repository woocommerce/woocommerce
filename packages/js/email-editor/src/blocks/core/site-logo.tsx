/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import type { BlockConfiguration, BlockEditProps } from '@wordpress/blocks';
import {
	PanelBody,
	Placeholder,
	RangeControl,
	Spinner,
	ToggleControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { select, useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerBlockVariationForEmail } from '../../config-tools';
import { updateBlockSettings } from '../../config-tools/block-config';
import { storeName } from '../../store';

// Core renders a site logo without a width at 120px.
const DEFAULT_WIDTH = 120;
const MIN_WIDTH = 20;

type SiteLogoAttributes = {
	width?: number;
	isLink: boolean;
	linkTarget: string;
	shouldSyncIcon?: boolean;
};

type SiteLogoEditProps = BlockEditProps< SiteLogoAttributes >;

type SelectFunction = typeof select;

type SiteLogo = {
	id?: number;
	url?: string;
	alt?: string;
	homeUrl?: string;
	siteTitle?: string;
	isLoading: boolean;
};

function getSiteLogo( selectFn: SelectFunction ): SiteLogo {
	const { getEntityRecord, hasFinishedResolution } = selectFn( coreStore );
	const site = getEntityRecord( 'root', '__unstableBase' ) as
		{ name?: string; home?: string; site_logo?: number } | undefined;
	const id = site?.site_logo || undefined;
	const mediaQuery = [ 'postType', 'attachment', id, { context: 'view' } ];
	const media = id
		? ( getEntityRecord( 'postType', 'attachment', id, {
				context: 'view',
			} ) as { source_url?: string; alt_text?: string } | undefined )
		: undefined;
	return {
		id,
		url: media?.source_url,
		alt: media?.alt_text,
		homeUrl: site?.home,
		siteTitle: decodeEntities( site?.name ?? '' ),
		isLoading:
			! site ||
			( !! id &&
				! hasFinishedResolution( 'getEntityRecord', mediaQuery ) ),
	};
}

function isReadOnly( selectFn: SelectFunction ): boolean {
	return !! selectFn( storeName ).getInitialEditorSettings()
		?.readOnlySiteIdentityBlocks;
}

function ReadOnlySiteLogoEdit( {
	attributes,
	setAttributes,
}: SiteLogoEditProps ) {
	const { width, isLink, linkTarget } = attributes;
	const logo = useSelect( getSiteLogo, [] );
	const maxWidth = useSelect(
		( selectFn ) =>
			(
				selectFn( blockEditorStore ).getSettings() as {
					maxWidth: number;
				}
			 ).maxWidth,
		[]
	);
	const blockProps = useBlockProps( {
		className: clsx( { 'is-default-size': ! width } ),
	} );

	let image = (
		<img
			className="custom-logo"
			src={ logo.url }
			alt={ logo.alt }
			width={ width }
		/>
	);
	if ( isLink ) {
		image = (
			<a
				href={ logo.homeUrl }
				className="custom-logo-link"
				rel="home"
				title={ logo.siteTitle }
				onClick={ ( event ) => event.preventDefault() }
			>
				{ image }
			</a>
		);
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody>
					<p>
						{ __(
							'This shows your site logo. To change it for the whole site, update it in your theme settings. To use a different image in this email only, transform this block into an Image block.',
							__i18n_text_domain__
						) }
					</p>
				</PanelBody>
				<ToolsPanel
					label={ __( 'Settings', __i18n_text_domain__ ) }
					resetAll={ () =>
						setAttributes( {
							width: undefined,
							isLink: true,
							linkTarget: '_self',
						} )
					}
				>
					<ToolsPanelItem
						hasValue={ () => !! width }
						label={ __( 'Image width', __i18n_text_domain__ ) }
						onDeselect={ () =>
							setAttributes( { width: undefined } )
						}
						isShownByDefault
					>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Image width', __i18n_text_domain__ ) }
							onChange={ ( newWidth ) =>
								setAttributes( { width: newWidth } )
							}
							min={ MIN_WIDTH }
							max={ maxWidth * 2.5 }
							initialPosition={ DEFAULT_WIDTH }
							value={ width }
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						hasValue={ () => ! isLink }
						label={ __(
							'Link image to home',
							__i18n_text_domain__
						) }
						onDeselect={ () => setAttributes( { isLink: true } ) }
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Link image to home',
								__i18n_text_domain__
							) }
							onChange={ () =>
								setAttributes( { isLink: ! isLink } )
							}
							checked={ isLink }
						/>
					</ToolsPanelItem>
					{ isLink && (
						<ToolsPanelItem
							hasValue={ () => linkTarget === '_blank' }
							label={ __(
								'Open in new tab',
								__i18n_text_domain__
							) }
							onDeselect={ () =>
								setAttributes( { linkTarget: '_self' } )
							}
							isShownByDefault
						>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Open in new tab',
									__i18n_text_domain__
								) }
								onChange={ ( value ) =>
									setAttributes( {
										linkTarget: value ? '_blank' : '_self',
									} )
								}
								checked={ linkTarget === '_blank' }
							/>
						</ToolsPanelItem>
					) }
				</ToolsPanel>
			</InspectorControls>
			{ logo.url ? (
				<div style={ { width } }>{ image }</div>
			) : (
				<Placeholder className="site-logo_placeholder" withIllustration>
					{ logo.isLoading && (
						<span className="components-placeholder__preview">
							<Spinner />
						</span>
					) }
				</Placeholder>
			) }
		</div>
	);
}

// Core's site logo edit writes the site-wide logo and icon, which the email editor saves together with the email.
function SiteLogoEdit(
	OriginalEditComponent: React.ComponentType< SiteLogoEditProps >
) {
	return function Edit( props: SiteLogoEditProps ) {
		const readOnly = useSelect( isReadOnly, [] );
		if ( readOnly ) {
			return <ReadOnlySiteLogoEdit { ...props } />;
		}
		return <OriginalEditComponent { ...props } />;
	};
}

const imageTransform = {
	type: 'block' as const,
	blocks: [ 'core/image' ],
	isMatch: () => isReadOnly( select ),
	transform: ( {
		width,
		isLink,
		linkTarget,
		shouldSyncIcon,
		...rest
	}: SiteLogoAttributes & Record< string, unknown > ) => {
		const logo = getSiteLogo( select );
		return createBlock( 'core/image', {
			...rest,
			url: logo.url,
			alt: logo.alt,
			id: logo.id,
			width: `${ width || DEFAULT_WIDTH }px`,
			...( isLink && {
				href: logo.homeUrl,
				linkTarget: linkTarget === '_blank' ? '_blank' : undefined,
				linkDestination: 'custom',
			} ),
		} );
	},
};

/**
 * Register a custom variation for the site logo block
 * This variation is used to display the site logo in the email editor by automatically adding some preset options.
 */
function registerCustomSiteLogoBlockVariation() {
	registerBlockVariationForEmail( 'core/site-logo', {
		name: 'site-logo-default',
		title: 'Site Logo',
		attributes: {
			align: 'center',
			width: 120, // set a default width for the site logo
		},
		isDefault: true, // set this as the default variation
	} );
}

function addReadOnlyMode() {
	updateBlockSettings( 'core/site-logo', ( current ) => {
		const settings = current as BlockConfiguration & {
			transforms?: { to?: unknown[] };
		};
		return {
			...current,
			edit: SiteLogoEdit(
				current.edit as React.ComponentType< SiteLogoEditProps >
			),
			transforms: {
				...settings.transforms,
				to: [ ...( settings.transforms?.to ?? [] ), imageTransform ],
			},
		} as typeof current;
	} );
}

/**
 * Enhance the Site Logo block.
 */
function enhanceSiteLogoBlock() {
	registerCustomSiteLogoBlockVariation();
	addReadOnlyMode();
}

export { enhanceSiteLogoBlock };
