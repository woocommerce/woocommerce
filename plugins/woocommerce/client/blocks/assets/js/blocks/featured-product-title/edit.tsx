/**
 * External dependencies
 */
import clsx from 'clsx';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement, forwardRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	AlignmentControl,
	BlockControls,
	InspectorControls,
	useBlockProps,
	PlainText,
	HeadingLevelDropdown,
} from '@wordpress/block-editor';
import {
	ToggleControl,
	TextControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

interface Props {
	attributes: {
		isLink: boolean;
		level: number;
		linkTarget: string;
		rel: string;
		textAlign?: string;
		content?: string;
	};
	setAttributes: ( attrs: Partial< Props[ 'attributes' ] > ) => void;
	context: {
		postId?: number;
		postType?: string;
		decoupledEdit?: boolean;
	};
}

const DEFAULT_ATTRIBUTES = {
	isLink: false,
	linkTarget: '_self',
	rel: '',
};

// Helper component to handle dynamic tag names without TypeScript union type issues.
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

	const { postId, postType, decoupledEdit } = context;
	const postTypeSlug = postType || 'product';

	const userCanEdit = useSelect(
		( select ) => {
			if ( ! postId ) return false;
			return select( coreStore ).canUser( 'update', {
				kind: 'postType',
				name: postTypeSlug,
				id: postId,
			} );
		},
		[ postId, postTypeSlug ]
	);

	const [ rawTitle = '', setTitle, fullTitle ] = useEntityProp(
		'postType',
		postTypeSlug,
		'title',
		postId ? String( postId ) : undefined
	);

	// Use the locally edited content whenever the `content` attribute has been
	// set (even if empty), so clearing the text keeps the block detached from
	// the entity instead of reverting to it.
	const hasDecoupledContent =
		decoupledEdit && attributes.content !== undefined;

	let displayRawTitle = '';
	if ( hasDecoupledContent ) {
		displayRawTitle = attributes.content as string;
	} else if ( typeof rawTitle === 'string' ) {
		displayRawTitle = rawTitle;
	}

	let displayFullTitle = '';
	if ( hasDecoupledContent ) {
		displayFullTitle = attributes.content as string;
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
			if ( ! postId ) return undefined;
			const record = select( coreStore ).getEntityRecord(
				'postType',
				postTypeSlug,
				postId
			);

			return record?.link;
		},
		[ postId, postTypeSlug ]
	);

	const onChangeTitle = ( v: string ) => {
		if ( decoupledEdit ) {
			setAttributes( { content: v } );
		} else {
			setTitle( v );
		}
	};

	// Decoupled edits only write to block attributes, so they do not depend on
	// the author's entity edit permissions. A page editor can always edit the
	// local text of a Featured block even without product edit caps.
	const canEditDecoupled = decoupledEdit;

	const blockProps = useBlockProps( {
		className: clsx( { [ `has-text-align-${ textAlign }` ]: textAlign } ),
	} );

	let titleElement: JSX.Element = createElement(
		TagName,
		blockProps,
		__( 'Product title', 'woocommerce' )
	) as JSX.Element;

	if ( postId ) {
		const readOnlyTitle = hasDecoupledContent ? (
			<ContainerElement tagName={ TagName } { ...blockProps }>
				{ displayFullTitle }
			</ContainerElement>
		) : (
			<ContainerElement
				tagName={ TagName }
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: displayFullTitle,
				} }
			/>
		);

		titleElement =
			canEditDecoupled || userCanEdit ? (
				<PlainText
					tagName={ TagName }
					placeholder={ __( 'No title', 'woocommerce' ) }
					value={ displayRawTitle }
					onChange={ onChangeTitle }
					__experimentalVersion={ 2 }
					{ ...blockProps }
				/>
			) : (
				readOnlyTitle
			);
	}

	if ( isLink && postId ) {
		const readOnlyLinkedTitle = hasDecoupledContent ? (
			<ContainerElement tagName={ TagName } { ...blockProps }>
				<a
					href={ link }
					target={ linkTarget }
					rel={ rel }
					onClick={ ( event ) => event.preventDefault() }
				>
					{ displayFullTitle }
				</a>
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

		titleElement =
			canEditDecoupled || userCanEdit ? (
				<ContainerElement tagName={ TagName } { ...blockProps }>
					<PlainText
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
						onChange={ onChangeTitle }
						__experimentalVersion={ 2 }
					/>
				</ContainerElement>
			) : (
				readOnlyLinkedTitle
			);
	}

	return (
		<>
			<BlockControls group="block">
				<HeadingLevelDropdown
					value={ level }
					onChange={ ( newLevel: number ) =>
						setAttributes( { level: newLevel } )
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
