/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useQueryLoopProductContextValidation } from '@woocommerce/base-hooks';
import {
	InspectorControls,
	useBlockProps,
	// @ts-expect-error missing types.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import TEMPLATE from './template';
import { ProductReviewsEditProps } from './types';
import { htmlElementMessages } from '../../utils/messages';

const Edit = ( {
	attributes,
	setAttributes,
	clientId,
	context,
}: ProductReviewsEditProps ) => {
	const { tagName: TagName = 'div' } = attributes;
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	const { hasInvalidContext, warningElement } =
		useQueryLoopProductContextValidation( {
			clientId,
			postType: context.postType,
			blockName: __( 'Product Reviews', 'woocommerce' ),
		} );

	if ( hasInvalidContext ) {
		return warningElement;
	}

	return (
		<>
			{ /* @ts-expect-error missing types */ }
			<InspectorControls group="advanced">
				<SelectControl
					// @ts-expect-error missing types.
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'HTML element', 'woocommerce' ) }
					options={ [
						{
							label: __( 'Default (<div>)', 'woocommerce' ),
							value: 'div',
						},
						{ label: '<section>', value: 'section' },
						{ label: '<aside>', value: 'aside' },
					] }
					value={ TagName }
					onChange={ ( value: 'div' | 'section' | 'aside' ) =>
						setAttributes( { tagName: value } )
					}
					help={ htmlElementMessages[ TagName ] }
				/>
			</InspectorControls>
			<TagName { ...innerBlocksProps } />
		</>
	);
};

export default Edit;
