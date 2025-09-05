/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
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
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';

interface Props {
	attributes: {
		level: number;
		textAlign?: string;
		isLink: boolean;
		rel: string;
		linkTarget: string;
	};
	setAttributes: ( attrs: Partial< Props[ 'attributes' ] > ) => void;
	context: {
		termId?: number;
		termTaxonomy?: string;
	};
}

export default function Edit( { attributes, setAttributes, context }: Props ) {
	const { level, textAlign, isLink, rel, linkTarget } = attributes;
	const TagName = (
		level === 0 ? 'p' : `h${ level }`
	) as keyof JSX.IntrinsicElements;

	const { termId, termTaxonomy } = context;

	const userCanEdit = useSelect(
		( select ) => {
			if ( ! termId ) return false;
			return ( select( coreStore ) as any ).canUser( 'update', {
				kind: 'taxonomy',
				name: termTaxonomy || 'product_cat',
				id: termId,
			} );
		},
		[ termId, termTaxonomy ]
	);

	const [ rawTitle = '', setTitle, fullTitle ] = ( useEntityProp as any )(
		'taxonomy',
		termTaxonomy || 'product_cat',
		'name',
		termId
	);

	const link: string | undefined = useSelect(
		( select ) => {
			if ( ! termId ) return undefined;
			const rec = ( select( coreStore ) as any ).getEntityRecord(
				'taxonomy',
				termTaxonomy || 'product_cat',
				termId
			);
			return rec?.link as string | undefined;
		},
		[ termId, termTaxonomy ]
	);

	const blockProps = useBlockProps( {
		className: clsx( { [ `has-text-align-${ textAlign }` ]: textAlign } ),
	} );

	const PlainTextAny = PlainText as any;

	const DynamicTag: any = TagName;

	let titleElement: JSX.Element = (
		<DynamicTag { ...blockProps }>
			{ __( 'Category title', 'woocommerce' ) }
		</DynamicTag>
	);

	if ( termId ) {
		titleElement = userCanEdit ? (
			<PlainTextAny
				tagName={ TagName as any }
				placeholder={ __( 'No title', 'woocommerce' ) as string }
				value={ rawTitle }
				onChange={ ( v: string ) =>
					( setTitle as ( v: string ) => void )( v )
				}
				__experimentalVersion={ 2 }
				{ ...blockProps }
			/>
		) : (
			<DynamicTag
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: ( fullTitle as any )?.rendered,
				} }
			/>
		);
	}

	if ( isLink && termId ) {
		titleElement = userCanEdit ? (
			<DynamicTag { ...blockProps }>
				<PlainTextAny
					tagName="a"
					href={ link }
					target={ linkTarget }
					rel={ rel }
					placeholder={
						! rawTitle?.length
							? ( __( 'No title', 'woocommerce' ) as string )
							: undefined
					}
					value={ rawTitle }
					onChange={ ( v: string ) =>
						( setTitle as ( v: string ) => void )( v )
					}
					__experimentalVersion={ 2 }
				/>
			</DynamicTag>
		) : (
			<DynamicTag { ...blockProps }>
				<a
					href={ link }
					target={ linkTarget }
					rel={ rel }
					onClick={ ( event ) => event.preventDefault() }
					dangerouslySetInnerHTML={ {
						__html: ( fullTitle as any )?.rendered,
					} }
				/>
			</DynamicTag>
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
					onChange={ ( nextAlign: string ) =>
						setAttributes( { textAlign: nextAlign || '' } )
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
								onChange={ ( value: boolean ) =>
									setAttributes( {
										linkTarget: value ? '_blank' : '_self',
									} )
								}
								checked={ linkTarget === '_blank' }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Link rel', 'woocommerce' ) }
								value={ rel }
								onChange={ ( newRel: string ) =>
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
