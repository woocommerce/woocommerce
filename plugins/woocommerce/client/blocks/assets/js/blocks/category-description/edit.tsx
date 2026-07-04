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
		content?: string;
	};
	setAttributes: ( attrs: Partial< Props[ 'attributes' ] > ) => void;
	context: {
		termId?: number;
		termTaxonomy?: string;
		decoupledEdit?: boolean;
	};
}

export default function Edit( { attributes, setAttributes, context }: Props ) {
	const { textAlign } = attributes;
	const { termId, termTaxonomy, decoupledEdit } = context;

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

	// Only use the locally edited content when it's non-empty; otherwise fall
	// back to the entity value, matching the PHP renderer's fallback to
	// `$term->description`. This also marks when `displayFullDescription`
	// holds raw, unsanitized `PlainText` input (needs to render as text, not
	// HTML).
	const hasDecoupledContent =
		decoupledEdit &&
		typeof attributes.content === 'string' &&
		attributes.content.length > 0;

	let displayRawDescription = '';
	if ( isPreviewMode ) {
		displayRawDescription = previewCategories[ 0 ].description;
	} else if ( hasDecoupledContent ) {
		displayRawDescription = attributes.content as string;
	} else if ( typeof rawDescription === 'string' ) {
		displayRawDescription = rawDescription;
	}

	let displayFullDescription = '';
	if ( isPreviewMode ) {
		displayFullDescription = previewCategories[ 0 ].description;
	} else if ( hasDecoupledContent ) {
		displayFullDescription = attributes.content as string;
	} else if (
		typeof fullDescription === 'object' &&
		fullDescription !== null &&
		'rendered' in fullDescription &&
		typeof fullDescription.rendered === 'string'
	) {
		displayFullDescription = fullDescription.rendered;
	}

	const onChangeDescription = ( v: string ) => {
		if ( decoupledEdit ) {
			setAttributes( { content: v } );
		} else {
			( setDescription as ( v: string ) => void )( v );
		}
	};

	const blockProps = useBlockProps( {
		className: clsx( { [ `has-text-align-${ textAlign }` ]: textAlign } ),
	} );

	let descriptionElement = (
		<p { ...blockProps }>{ __( 'Category description', 'woocommerce' ) }</p>
	);

	if ( termId ) {
		const readOnlyDescription = hasDecoupledContent ? (
			<p { ...blockProps }>{ displayFullDescription }</p>
		) : (
			<p
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: displayFullDescription,
				} }
			/>
		);

		descriptionElement = userCanEdit ? (
			<PlainText
				tagName="p"
				placeholder={ __( 'No description', 'woocommerce' ) as string }
				value={ displayRawDescription }
				onChange={ onChangeDescription }
				__experimentalVersion={ 2 }
				{ ...blockProps }
			/>
		) : (
			readOnlyDescription
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
