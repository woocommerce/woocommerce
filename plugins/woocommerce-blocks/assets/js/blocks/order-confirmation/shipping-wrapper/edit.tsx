/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		heading: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const blockProps = useBlockProps();

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
						'woocommerce/order-confirmation-shipping-address',
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
