/**
 * External dependencies
 */

import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	Warning,
	store as blockEditorStore,
	// @ts-expect-error missing types.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import TEMPLATE from './template';
import { ProductReviewsEditProps } from './types';
import { htmlElementMessages } from '../../utils/messages';

/**
 * Check if block is inside a Query Loop with non-product post type
 *
 * @param {string} clientId The block's client ID
 * @param {string} postType The current post type
 * @return {boolean} Whether the block is in an invalid Query Loop context
 */
const useIsInvalidQueryLoopContext = ( clientId: string, postType: string ) => {
	const { getBlockParentsByBlockName } = useSelect( blockEditorStore, [] );
	const blockParents = useMemo( () => {
		return getBlockParentsByBlockName( clientId, 'core/post-template' );
	}, [ getBlockParentsByBlockName, clientId ] );

	return blockParents.length > 0 && postType !== 'product';
};

const Edit = ( {
	attributes,
	setAttributes,
	clientId,
	context,
}: ProductReviewsEditProps ) => {
	const { tagName: TagName } = attributes;
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	const { postType: contextPostType } = context;
	const isInvalidQueryLoopContext = useIsInvalidQueryLoopContext(
		clientId,
		contextPostType
	);
	if ( isInvalidQueryLoopContext ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'The Product Reviews block requires a product context. When used in a Query Loop, the Query Loop must be configured to display products.',
						'woocommerce'
					) }
				</Warning>
			</div>
		);
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
