/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	AlignmentControl,
	BlockControls,
	HeadingLevelDropdown,
	InspectorControls,
	useBlockEditingMode,
	useBlockProps,
} from '@wordpress/block-editor';
import { createBlock, getBlockType } from '@wordpress/blocks';
import type { BlockConfiguration, BlockEditProps } from '@wordpress/blocks';
import {
	PanelBody,
	ToggleControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { select, useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { create, toHTMLString } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';
import { storeName } from '../../store';

type HeadingLevel = 0 | 1 | 2 | 3 | 4 | 5 | 6;

type SiteTitleAttributes = {
	level: HeadingLevel;
	levelOptions?: HeadingLevel[];
	textAlign?: string;
	isLink: boolean;
	linkTarget: string;
};

type SiteTitleEditProps = BlockEditProps< SiteTitleAttributes >;

type SelectFunction = typeof select;

function getSiteTitle( selectFn: SelectFunction ): string {
	const site = selectFn( coreStore ).getEntityRecord(
		'root',
		'__unstableBase'
	) as { name?: string } | undefined;
	return decodeEntities( site?.name ?? '' );
}

function isReadOnly( selectFn: SelectFunction ): boolean {
	return !! selectFn( storeName ).getInitialEditorSettings()
		?.readOnlySiteIdentityBlocks;
}

function ReadOnlySiteTitleEdit( {
	attributes,
	setAttributes,
}: SiteTitleEditProps ) {
	const { level, levelOptions, textAlign, isLink, linkTarget } = attributes;
	const title = useSelect( getSiteTitle, [] );
	const blockEditingMode = useBlockEditingMode();
	// From WordPress 7.1 the block has no textAlign attribute; the typography block support adds its own control.
	const hasTextAlignAttribute =
		!! getBlockType( 'core/site-title' )?.attributes?.textAlign;
	const TagName = ( level === 0 ? 'p' : `h${ level }` ) as
		'p' | 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
	const blockProps = useBlockProps( {
		className: clsx( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );
	const text = title || __( 'Site Title placeholder', __i18n_text_domain__ );

	return (
		<>
			{ blockEditingMode === 'default' && (
				<BlockControls group="block">
					<HeadingLevelDropdown
						value={ level }
						options={ levelOptions }
						onChange={ ( newLevel ) =>
							setAttributes( { level: newLevel } )
						}
					/>
					{ hasTextAlignAttribute && (
						<AlignmentControl
							value={ textAlign }
							onChange={ ( nextAlign?: string ) =>
								setAttributes( { textAlign: nextAlign } )
							}
						/>
					) }
				</BlockControls>
			) }
			<InspectorControls>
				<PanelBody>
					<p>
						{ __(
							'This shows your site title. To change it for the whole site, go to Settings > General. To use a different title in this email only, transform this block into a Heading block.',
							__i18n_text_domain__
						) }
					</p>
				</PanelBody>
				<ToolsPanel
					label={ __( 'Settings', __i18n_text_domain__ ) }
					resetAll={ () =>
						setAttributes( { isLink: true, linkTarget: '_self' } )
					}
				>
					<ToolsPanelItem
						hasValue={ () => ! isLink }
						label={ __(
							'Make title link to home',
							__i18n_text_domain__
						) }
						onDeselect={ () => setAttributes( { isLink: true } ) }
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Make title link to home',
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
							hasValue={ () => linkTarget !== '_self' }
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
			<TagName { ...blockProps }>
				{ isLink ? (
					<a
						href="#site-title-pseudo-link"
						onClick={ ( event ) => event.preventDefault() }
					>
						{ text }
					</a>
				) : (
					<span>{ text }</span>
				) }
			</TagName>
		</>
	);
}

// Core's site title edit writes the site-wide title, which the email editor saves together with the email.
function SiteTitleEdit(
	OriginalEditComponent: React.ComponentType< SiteTitleEditProps >
) {
	return function Edit( props: SiteTitleEditProps ) {
		const readOnly = useSelect( isReadOnly, [] );
		if ( readOnly ) {
			return <ReadOnlySiteTitleEdit { ...props } />;
		}
		return <OriginalEditComponent { ...props } />;
	};
}

const headingTransform = {
	type: 'block' as const,
	blocks: [ 'core/heading' ],
	isMatch: () => isReadOnly( select ),
	transform: ( {
		level,
		levelOptions,
		textAlign,
		isLink,
		linkTarget,
		...rest
	}: SiteTitleAttributes & Record< string, unknown > ) =>
		createBlock( 'core/heading', {
			...rest,
			content: toHTMLString( {
				value: create( { text: getSiteTitle( select ) } ),
			} ),
			// Level 0 renders the site title as a paragraph; the heading falls back to its default level.
			...( level ? { level } : {} ),
			textAlign,
		} ),
};

function enhanceSiteTitleBlock() {
	updateBlockSettings( 'core/site-title', ( current ) => {
		const settings = current as BlockConfiguration & {
			transforms?: { to?: unknown[] };
		};
		return {
			...current,
			edit: SiteTitleEdit(
				current.edit as React.ComponentType< SiteTitleEditProps >
			),
			transforms: {
				...settings.transforms,
				to: [ ...( settings.transforms?.to ?? [] ), headingTransform ],
			},
		} as typeof current;
	} );
}

export { enhanceSiteTitleBlock };
