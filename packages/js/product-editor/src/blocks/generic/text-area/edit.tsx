/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { createElement } from '@wordpress/element';
import { BaseControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
import { BlockControls, RichText } from '@wordpress/block-editor';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { RTLToolbarButton } from './toolbar/toolbar-button-rtl';
import type { TextAreaBlockEdit } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import AligmentToolbarButton from './toolbar/toolbar-button-alignment';

export function TextAreaBlockEdit( {
	attributes,
	setAttributes,
	context,
}: ProductEditorBlockEditProps< TextAreaBlockEdit > ) {
	const { align, allowedFormats, direction, label, helpText } = attributes;
	const blockProps = useWooBlockProps( attributes, {
		style: { direction },
	} );

	const contentId = useInstanceId(
		TextAreaBlockEdit,
		'wp-block-woocommerce-product-content-field__content'
	);

	// `property` attribute is required.
	const { property } = attributes;
	if ( ! property ) {
		throw new Error(
			__( 'Property attribute is required.', 'woocommerce' )
		);
	}

	const [ content, setContent ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		property
	);

	function setAligment( value: TextAreaBlockEdit[ 'align' ] ) {
		setAttributes( { align: value } );
	}

	function changeDirection( value: TextAreaBlockEdit[ 'direction' ] ) {
		setAttributes( { direction: value } );
	}

	const blockControlsProps = { group: 'block' };

	return (
		<div className={ 'wp-block-woocommerce-product-text-area-field' }>
			<BlockControls { ...blockControlsProps }>
				<AligmentToolbarButton
					align={ align }
					setAligment={ setAligment }
				/>

				<RTLToolbarButton
					direction={ direction }
					onChange={ changeDirection }
				/>
			</BlockControls>

			<BaseControl
				id={ contentId.toString() }
				label={ label }
				help={ helpText }
			>
				<div { ...blockProps }>
					<RichText
						id={ contentId.toString() }
						identifier="content"
						tagName="p"
						value={ content }
						onChange={ setContent }
						data-empty={ Boolean( content ) }
						className={ classNames( 'components-content-control', {
							[ `has-text-align-${ align }` ]: align,
						} ) }
						dir={ direction }
						allowedFormats={ allowedFormats }
					/>
				</div>
			</BaseControl>
		</div>
	);
}
