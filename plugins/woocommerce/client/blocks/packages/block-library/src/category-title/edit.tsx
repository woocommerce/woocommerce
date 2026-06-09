/**
 * External dependencies
 */
import clsx from 'clsx';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement, forwardRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { WP_REST_API_Category } from 'wp-types';
import {
	AlignmentControl,
	BlockControls,
	InspectorControls,
	useBlockProps,
	PlainText,
	HeadingLevelDropdown,
} from '@wordpress/block-editor';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	ToggleControl,
	TextControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { usePreviewMode } from '../hooks/use-preview-mode';

export type CategoryTitleAttributes = {
	isLink: boolean;
	level: number;
	linkTarget: string;
	rel: string;
	textAlign?: string;
};

interface Props {
	attributes: CategoryTitleAttributes;
	setAttributes: ( attrs: Partial< CategoryTitleAttributes > ) => void;
	context: {
		termId?: number;
		termTaxonomy?: string;
	};
}

const DEFAULT_ATTRIBUTES = {
	isLink: false,
	linkTarget: '_self',
	rel: '',
};

const PREVIEW_CATEGORY_TITLE = __( 'Hoodies', 'woocommerce' );

type CoreDataSelector = {
	canUser: (
		action: string,
		resource: {
			kind: string;
			name: string;
			id: number;
		}
	) => boolean | undefined;
	getEntityRecord: < T >(
		kind: string,
		name: string,
		id: number
	) => T | undefined;
};

type HeadingLevel = 0 | 1 | 2 | 3 | 4 | 5 | 6;

const PlainTextWithTagName = PlainText as unknown as React.ComponentType<
	Record< string, unknown >
>;

const ContainerElement = forwardRef<
	HTMLElement,
	React.HTMLAttributes< HTMLElement > & {
		tagName?: string;
		children?: React.ReactNode;
	}
>( ( { tagName, children, ...props }, ref ) => {
	return createElement( tagName as string, { ...props, ref }, children );
} );

export default function Edit( { attributes, setAttributes, context }: Props ) {
	const { isLink, level, linkTarget, rel, textAlign } = attributes;
	const TagName = (
		level === 0 ? 'p' : `h${ level }`
	) as keyof JSX.IntrinsicElements;

	const { termId, termTaxonomy } = context;

	const userCanEdit = useSelect(
		( select ) => {
			if ( ! termId ) return false;
			const coreDataSelector = select(
				coreStore
			) as unknown as CoreDataSelector;

			return Boolean(
				coreDataSelector.canUser( 'update', {
					kind: 'taxonomy',
					name: termTaxonomy || 'product_cat',
					id: termId,
				} )
			);
		},
		[ termId, termTaxonomy ]
	);

	const isPreviewMode = usePreviewMode();
	const [ rawTitle = '', setTitle, fullTitle ] = useEntityProp(
		'taxonomy',
		termTaxonomy || 'product_cat',
		'name',
		termId ? String( termId ) : undefined
	);

	let displayRawTitle = '';
	if ( isPreviewMode ) {
		displayRawTitle = PREVIEW_CATEGORY_TITLE;
	} else if ( typeof rawTitle === 'string' ) {
		displayRawTitle = rawTitle;
	}

	let displayFullTitle = '';
	if ( isPreviewMode ) {
		displayFullTitle = PREVIEW_CATEGORY_TITLE;
	} else if (
		typeof fullTitle === 'object' &&
		fullTitle !== null &&
		'rendered' in fullTitle &&
		typeof fullTitle.rendered === 'string'
	) {
		displayFullTitle = fullTitle.rendered;
	}

	const link = useSelect(
		( select ) => {
			if ( ! termId ) return undefined;
			const coreDataSelector = select(
				coreStore
			) as unknown as CoreDataSelector;
			const record =
				coreDataSelector.getEntityRecord< WP_REST_API_Category >(
					'taxonomy',
					termTaxonomy || 'product_cat',
					termId
				);

			return record?.link;
		},
		[ termId, termTaxonomy ]
	);

	const blockProps = useBlockProps( {
		className: clsx( { [ `has-text-align-${ textAlign }` ]: textAlign } ),
	} );

	let titleElement: JSX.Element = createElement(
		TagName,
		blockProps,
		__( 'Category title', 'woocommerce' )
	) as JSX.Element;

	if ( termId ) {
		titleElement = userCanEdit ? (
			<PlainTextWithTagName
				tagName={ TagName }
				placeholder={ __( 'No title', 'woocommerce' ) }
				value={ displayRawTitle }
				onChange={ ( v: string ) => setTitle( v ) }
				__experimentalVersion={ 2 }
				{ ...blockProps }
			/>
		) : (
			<ContainerElement
				tagName={ TagName }
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: displayFullTitle,
				} }
			/>
		);
	}

	if ( isLink && termId ) {
		titleElement = userCanEdit ? (
			<ContainerElement tagName={ TagName } { ...blockProps }>
				<PlainTextWithTagName
					tagName="a"
					href={ link }
					target={ linkTarget }
					rel={ rel }
					placeholder={
						! displayRawTitle?.length
							? __( 'No title', 'woocommerce' )
							: undefined
					}
					value={ displayRawTitle }
					onChange={ ( v: string ) => setTitle( v ) }
					__experimentalVersion={ 2 }
				/>
			</ContainerElement>
		) : (
			<ContainerElement tagName={ TagName } { ...blockProps }>
				<a
					href={ link }
					target={ linkTarget }
					rel={ rel }
					onClick={ ( event ) => event.preventDefault() }
					dangerouslySetInnerHTML={ {
						__html: displayFullTitle,
					} }
				/>
			</ContainerElement>
		);
	}

	return (
		<>
			<BlockControls group="block">
				<HeadingLevelDropdown
					value={ level as HeadingLevel }
					onChange={ ( newLevel?: HeadingLevel ) =>
						setAttributes( { level: newLevel ?? 2 } )
					}
				/>
				<AlignmentControl
					value={ textAlign }
					onChange={ ( newTextAlign: string | undefined ) =>
						setAttributes( { textAlign: newTextAlign || '' } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Settings', 'woocommerce' ) }
					resetAll={ () => {
						setAttributes( DEFAULT_ATTRIBUTES );
					} }
				>
					<ToolsPanelItem
						label={ __( 'Make title a link', 'woocommerce' ) }
						hasValue={ () => isLink !== DEFAULT_ATTRIBUTES.isLink }
						onDeselect={ () =>
							setAttributes( {
								isLink: DEFAULT_ATTRIBUTES.isLink,
							} )
						}
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Make title a link', 'woocommerce' ) }
							onChange={ () =>
								setAttributes( { isLink: ! isLink } )
							}
							checked={ isLink }
						/>
					</ToolsPanelItem>
					{ isLink && (
						<>
							<ToolsPanelItem
								label={ __( 'Open in new tab', 'woocommerce' ) }
								hasValue={ () =>
									linkTarget !== DEFAULT_ATTRIBUTES.linkTarget
								}
								onDeselect={ () =>
									setAttributes( {
										linkTarget:
											DEFAULT_ATTRIBUTES.linkTarget,
									} )
								}
								isShownByDefault
							>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __(
										'Open in new tab',
										'woocommerce'
									) }
									onChange={ ( v ) =>
										setAttributes( {
											linkTarget: v ? '_blank' : '_self',
										} )
									}
									checked={ linkTarget === '_blank' }
								/>
							</ToolsPanelItem>
							<ToolsPanelItem
								label={ __( 'Link rel', 'woocommerce' ) }
								hasValue={ () =>
									rel !== DEFAULT_ATTRIBUTES.rel
								}
								onDeselect={ () =>
									setAttributes( {
										rel: DEFAULT_ATTRIBUTES.rel,
									} )
								}
								isShownByDefault
							>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Link rel', 'woocommerce' ) }
									value={ rel }
									onChange={ ( newRel ) =>
										setAttributes( { rel: newRel } )
									}
								/>
							</ToolsPanelItem>
						</>
					) }
				</ToolsPanel>
			</InspectorControls>
			{ titleElement }
		</>
	);
}
