/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, Placeholder } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { Icon, percent } from '@wordpress/icons';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { Attributes } from './types';
import { ProductOnSaleInspectorControls } from './inspector-controls';

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	name: string;
}

const EmptyPlaceholder = () => (
	<Placeholder
		icon={ <Icon icon={ percent } /> }
		label={ __( 'On Sale Products', 'woocommerce' ) }
		className="wc-block-product-on-sale"
	>
		{ __(
			'This block shows on-sale products. There are currently no discounted products in your store.',
			'woocommerce'
		) }
	</Placeholder>
);

/**
 * Component to handle edit mode of "On Sale Products".
 */
const ProductOnSaleBlock: React.FunctionComponent< Props > = (
	props: Props
) => {
	const { attributes, setAttributes, name } = props;
	const blockProps = useBlockProps();

	if ( attributes.isPreview ) {
		return <div { ...blockProps }>{ gridBlockPreview }</div>;
	}

	return (
		<div { ...blockProps }>
			<ProductOnSaleInspectorControls
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<Disabled>
				<ServerSideRender
					block={ name }
					attributes={ attributes }
					EmptyResponsePlaceholder={ EmptyPlaceholder }
				/>
			</Disabled>
		</div>
	);
};

export default ProductOnSaleBlock;
