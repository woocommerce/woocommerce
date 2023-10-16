/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { DialogBlockAttributes } from './types';
import { __ } from '@wordpress/i18n';

export function Edit( {
	attributes,
	setAttributes,
}: ProductEditorBlockEditProps< DialogBlockAttributes > ) {
	const { title, isOpen, onClose } = attributes;

	const blockProps = useWooBlockProps( {
		...attributes,
	} );

	const { children, innerBlockProps } = useInnerBlocksProps( blockProps, {
		templateLock: 'all',
	} );

	function handleClose() {
		setAttributes( { isOpen: false } );
		if ( onClose ) onClose();
	}

	return (
		<div { ...innerBlockProps }>
			{ isOpen && (
				<Modal title={ title } onRequestClose={ handleClose }>
					{ children }

					<div className="wp-block-woocommerce-product-dialog-field__actions">
						<Button variant="secondary" onClick={ handleClose }>
							{ __( 'Close', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
