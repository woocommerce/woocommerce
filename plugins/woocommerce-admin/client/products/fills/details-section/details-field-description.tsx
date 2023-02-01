/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useFormContext2,
	__experimentalRichTextEditor as RichTextEditor,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { BlockInstance, serialize, parse } from '@wordpress/blocks';
import { useState } from '@wordpress/element';

export const DetailsDescriptionField = () => {
	const { setValue, getValues } = useFormContext2< Product >();
	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( parse( getValues().description || '' ) );

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
