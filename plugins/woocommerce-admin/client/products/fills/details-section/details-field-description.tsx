/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useFormContext,
	__experimentalRichTextEditor as RichTextEditor,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { BlockInstance, serialize, parse } from '@wordpress/blocks';
import { useEffect, useState } from '@wordpress/element';

export const DetailsDescriptionField = () => {
	const { setValue, values } = useFormContext< Product >();
	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( parse( values.description || '' ) );

	useEffect( () => {
		console.log("desc", values?.description);
		setDescriptionBlocks( parse( values.description || '' ) );
	}, [ values?.description ] );
	
	return (
		<RichTextEditor
			label={ __( 'Description', 'woocommerce' ) }
			blocks={ descriptionBlocks }
			onChange={ ( blocks ) => {
				setDescriptionBlocks( blocks );
				if ( ! descriptionBlocks.length ) {
					return;
				}
				setValue( 'description', serialize( blocks ) );
			} }
			placeholder={ __(
				'Describe this product. What makes it unique? What are its most important features?',
				'woocommerce'
			) }
		/>
	);
};
