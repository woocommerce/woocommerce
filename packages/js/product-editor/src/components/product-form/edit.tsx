/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { FormProvider, useForm } from 'react-hook-form';

export function Edit( { attributes, setAttributes }: Record< string, any > ) {
	const blockProps = useBlockProps();
	const methods = useForm( {
		defaultValues: {
			name: 'Product name',
			summary: '',
		},
		mode: 'all',
	} );
	const onSubmit = ( data: any ) => console.log( data );

	return (
		<div { ...blockProps }>
			<FormProvider { ...methods }>
				<form onSubmit={ methods.handleSubmit( onSubmit ) }>
					<InnerBlocks
						template={ [
							[ 'woocommerce/product-name', {} ],
							[ 'woocommerce/product-submit', {} ],
						] }
						templateLock="all"
					/>
				</form>
			</FormProvider>
		</div>
	);
}
