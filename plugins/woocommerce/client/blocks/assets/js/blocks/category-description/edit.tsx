/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import {
	AlignmentControl,
	BlockControls,
	useBlockProps,
	PlainText,
} from '@wordpress/block-editor';
import { usePreviewMode } from '@woocommerce/base-hooks';
import { previewCategories } from '@woocommerce/resource-previews';

interface Props {
	attributes: {
		textAlign?: string;
	};
	setAttributes: ( attrs: Partial< Props[ 'attributes' ] > ) => void;
	context: {
		termId?: number;
		termTaxonomy?: string;
	};
}

export default function Edit( { attributes, setAttributes, context }: Props ) {
	const { textAlign } = attributes;
	const { termId, termTaxonomy } = context;

	const userCanEdit = useSelect(
		( select ) => {
			if ( ! termId ) return false;
			// This use actually reflects the use seen in `core/post-title` block.
			return select( coreStore ).canUser( 'update', {
				kind: 'taxonomy',
				name: termTaxonomy || 'product_cat',
				id: termId,
			} );
		},
		[ termId, termTaxonomy ]
	);

	const [ rawDescription = '', setDescription, fullDescription ] =
		useEntityProp(
			'taxonomy',
			termTaxonomy || 'product_cat',
			'description',
			String( termId )
		);

	const isPreviewMode = usePreviewMode();

	let displayRawDescription = '';
	if ( isPreviewMode ) {
		displayRawDescription = previewCategories[ 0 ].description;
	} else if ( typeof rawDescription === 'string' ) {
		displayRawDescription = rawDescription;
	}

	let displayFullDescription = '';
	if ( isPreviewMode ) {
		displayFullDescription = previewCategories[ 0 ].description;
	} else if (
		typeof fullDescription === 'object' &&
		fullDescription !== null &&
		'rendered' in fullDescription &&
		typeof fullDescription.rendered === 'string'
	) {
		displayFullDescription = fullDescription.rendered;
	}

	const blockProps = useBlockProps( {
		className: clsx( { [ `has-text-align-${ textAlign }` ]: textAlign } ),
	} );

	let descriptionElement = (
		<p { ...blockProps }>{ __( 'Category description', 'woocommerce' ) }</p>
	);

	if ( termId ) {
		descriptionElement = userCanEdit ? (
			<PlainText
				tagName="p"
				placeholder={ __( 'No description', 'woocommerce' ) as string }
				value={ displayRawDescription }
				onChange={ ( v: string ) =>
					( setDescription as ( v: string ) => void )( v )
				}
				__experimentalVersion={ 2 }
				{ ...blockProps }
			/>
		) : (
			<p
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: displayFullDescription,
				} }
			/>
		);
	}

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					value={ textAlign }
					onChange={ ( nextAlign: string ) =>
						setAttributes( { textAlign: nextAlign || '' } )
					}
				/>
			</BlockControls>
			{ descriptionElement }
		</>
	);
}
