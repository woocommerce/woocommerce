/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, Placeholder } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { Icon, percent } from '@wordpress/icons';

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
		label={ __( 'On Sale Products', 'woo-gutenberg-products-block' ) }
		className="wc-block-product-on-sale"
	>
		{ __(
			'This block shows on-sale products. There are currently no discounted products in your store.',
			'woo-gutenberg-products-block'
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

	if ( attributes.isPreview ) {
		return gridBlockPreview;
	}

	return (
		<>
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
		</>
	);
};

export default ProductOnSaleBlock;
