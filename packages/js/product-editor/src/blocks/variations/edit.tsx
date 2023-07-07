/**
 * External dependencies
 */
import classNames from 'classnames';
import type { BlockEditProps } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { Product } from '@woocommerce/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';
import { VariationsBlockAttributes } from './types';
import { EmptyVariationsImage } from './empty-variations-image';

function hasAttributesUsedForVariations(
	productAttributes: Product[ 'attributes' ]
) {
	return productAttributes.some( ( { variation } ) => variation );
}

export function Edit( {
	attributes,
}: BlockEditProps< VariationsBlockAttributes > ) {
	const { description } = attributes;

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);

	const hasAttributes = hasAttributesUsedForVariations( productAttributes );

	const blockProps = useBlockProps( {
		className: classNames( {
			'wp-block-woocommerce-product-variations-fields--has-attributes':
				hasAttributes,
		} ),
	} );
	const innerBlockProps = useInnerBlocksProps(
		{
			className:
				'wp-block-woocommerce-product-variations-fields__content',
		},
		{ templateLock: 'all' }
	);

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-variations-fields__heading">
				<div className="wp-block-woocommerce-product-variations-fields__heading-image-container">
					<EmptyVariationsImage />
				</div>
				<p
					className="wp-block-woocommerce-product-variations-fields__heading-description"
					dangerouslySetInnerHTML={ sanitizeHTML( description ) }
				/>
				<div className="wp-block-woocommerce-product-variations-fields__heading-actions">
					<Button variant="primary" aria-disabled="true">
						{ __( 'Add variation options', 'woocommerce' ) }
					</Button>
				</div>
			</div>

			<div { ...innerBlockProps } />
		</div>
	);
}
