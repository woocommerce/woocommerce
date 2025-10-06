/**
 * External dependencies
 */
import clsx from 'clsx';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement, forwardRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WP_REST_API_Category } from 'wp-types';
import {
	// @ts-expect-error AlignmentControl is not exported from @wordpress/block-editor
	AlignmentControl,
	BlockControls,
	InspectorControls,
	useBlockProps,
	PlainText,
	// @ts-expect-error HeadingLevelDropdown is not exported from @wordpress/block-editor
	HeadingLevelDropdown,
} from '@wordpress/block-editor';

interface Props {
	attributes: {
		isLink: boolean;
		level: number;
		linkTarget: string;
		rel: string;
		textAlign?: string;
	};
	setAttributes: ( attrs: Partial< Props[ 'attributes' ] > ) => void;
	context: {
		termId?: number;
		termTaxonomy?: string;
	};
}

// Helper component to handle dynamic tag names without TypeScript union type issues
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
			// @ts-expect-error canUser is not typed correctly
			// This use actually reflects the use seen in `core/post-title` block.
			return select( coreStore ).canUser( 'update', {
				kind: 'taxonomy',
				name: termTaxonomy || 'product_cat',
				id: termId,
			} );
		},
		[ termId, termTaxonomy ]
	);

	const [ rawTitle = '', setTitle, fullTitle ] = useEntityProp(
		'taxonomy',
		termTaxonomy || 'product_cat',
		'name',
		termId ? String( termId ) : undefined
	);

	const link = useSelect(
		( select ) => {
			if ( ! termId ) return undefined;
			const record = select(
				coreStore
			).getEntityRecord< WP_REST_API_Category >(
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
			<PlainText
				// @ts-expect-error PlainText component types are not up-to-date
				tagName={ TagName }
				placeholder={ __( 'No title', 'woocommerce' ) }
				value={ rawTitle }
				onChange={ ( v ) => setTitle( v ) }
				__experimentalVersion={ 2 }
				{ ...blockProps }
			/>
		) : (
			<ContainerElement
				tagName={ TagName }
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: fullTitle?.rendered,
				} }
			/>
		);
	}

	if ( isLink && termId ) {
		titleElement = userCanEdit ? (
			<ContainerElement tagName={ TagName } { ...blockProps }>
				<PlainText
					// @ts-expect-error PlainText component types are not up-to-date
					tagName="a"
					href={ link }
					target={ linkTarget }
					rel={ rel }
					placeholder={
						! rawTitle?.length
							? __( 'No title', 'woocommerce' )
							: undefined
					}
					value={ rawTitle }
					onChange={ ( v ) => setTitle( v ) }
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
						__html: fullTitle?.rendered,
					} }
				/>
			</ContainerElement>
		);
	}

	return (
		<>
			{ /* @ts-expect-error BlockControls typing */ }
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
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Make title a link', 'woocommerce' ) }
						onChange={ () => setAttributes( { isLink: ! isLink } ) }
						checked={ isLink }
					/>
					{ isLink && (
						<>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Open in new tab', 'woocommerce' ) }
								onChange={ ( v ) =>
									setAttributes( {
										linkTarget: v ? '_blank' : '_self',
									} )
								}
								checked={ linkTarget === '_blank' }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Link rel', 'woocommerce' ) }
								value={ rel }
								onChange={ ( newRel ) =>
									setAttributes( { rel: newRel } )
								}
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>
			{ titleElement }
		</>
	);
}
