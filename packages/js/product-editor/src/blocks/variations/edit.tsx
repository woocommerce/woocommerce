/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
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

export function Edit( {
	attributes,
}: BlockEditProps< VariationsBlockAttributes > ) {
	const { description } = attributes;
	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( {}, { templateLock: 'all' } );

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
