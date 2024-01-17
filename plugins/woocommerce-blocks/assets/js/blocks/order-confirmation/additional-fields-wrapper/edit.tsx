/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import {
	ADDITIONAL_FORM_FIELDS,
	CONTACT_FORM_FIELDS,
} from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		heading: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-additional-fields-wrapper',
	} );

	const additionalFields = {
		...ADDITIONAL_FORM_FIELDS,
		...CONTACT_FORM_FIELDS,
	};

	if ( Object.entries( additionalFields ).length === 0 ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ [ 'core/heading' ] }
				template={ [
					[
						'core/heading',
						{
							level: 3,
							style: { typography: { fontSize: '24px' } },
							content: attributes.heading || '',
							onChangeContent: ( value: string ) =>
								setAttributes( { heading: value } ),
						},
					],
					[
						'woocommerce/order-confirmation-additional-fields',
						{
							lock: {
								remove: true,
							},
						},
					],
				] }
			/>
		</div>
	);
};

export default Edit;
