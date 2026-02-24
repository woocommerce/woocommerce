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
	useBlockProps,
	PlainText,
} from '@wordpress/block-editor';
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

	const [ rawDescription = '', setDescription, fullDescription ] =
		useEntityProp(
			'taxonomy',
			termTaxonomy || 'product_cat',
			'description',
			String( termId )
		);

	const blockProps = useBlockProps( {
		className: clsx( { [ `has-text-align-${ textAlign }` ]: textAlign } ),
	} );

	let descriptionElement = (
		<p { ...blockProps }>{ __( 'Category description', 'woocommerce' ) }</p>
	);

	if ( termId ) {
		descriptionElement = userCanEdit ? (
			<PlainText
				// @ts-expect-error PlainText component types are not up-to-date
				tagName="p"
				placeholder={ __( 'No description', 'woocommerce' ) as string }
				value={ rawDescription }
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
					__html: fullDescription?.rendered,
				} }
			/>
		);
	}

	return (
		<>
			{ /* @ts-expect-error BlockControls typing */ }
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
