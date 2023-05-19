/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { parse, serialize } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ContentPreview } from '../../components/content-preview';
import { ModalEditor } from '../../components/modal-editor';

/**
 * Internal dependencies
 */

export function Edit() {
	const blockProps = useBlockProps();
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	return (
		<div { ...blockProps }>
			<Button
				variant="secondary"
				onClick={ () => setIsModalOpen( true ) }
			>
				{ description.length
					? __( 'Edit description', 'woocommerce' )
					: __( 'Add description', 'woocommerce' ) }
			</Button>
			{ isModalOpen && (
				<ModalEditor
					initialBlocks={ parse( description ) }
					onChange={ ( blocks ) => {
						const html = serialize( blocks );
						setDescription( html );
					} }
					onClose={ () => setIsModalOpen( false ) }
					title={ __( 'Edit description', 'woocommerce' ) }
				/>
			) }
			{ !! description.length && (
				<ContentPreview content={ description } />
			) }
		</div>
	);
}
