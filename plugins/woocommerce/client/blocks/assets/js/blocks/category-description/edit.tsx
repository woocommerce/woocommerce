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

	// Use the locally edited content whenever the `content` attribute has been
	// set (even if empty), so clearing the text keeps the block detached from
	// the entity instead of reverting to it.
	const hasDecoupledContent =
		decoupledEdit && attributes.content !== undefined;

	let displayRawDescription = '';
	if ( isPreviewMode ) {
		displayRawDescription = previewCategories[ 0 ]?.description ?? '';
	} else if ( hasDecoupledContent ) {
		displayRawDescription = attributes.content as string;
	} else if ( typeof rawDescription === 'string' ) {
		displayRawDescription = rawDescription;
	}

	let displayFullDescription = '';
	if ( isPreviewMode ) {
		displayFullDescription = previewCategories[ 0 ]?.description ?? '';
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

	// Decoupled edits only write to block attributes, so they do not depend on
	// the author's entity edit permissions. A page editor can always edit the
	// local text of a Featured block even without product/category caps.
	const canEditDecoupled = decoupledEdit;

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

		descriptionElement =
			canEditDecoupled || userCanEdit ? (
				<PlainText
					tagName="p"
					placeholder={
						__( 'No description', 'woocommerce' ) as string
					}
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
