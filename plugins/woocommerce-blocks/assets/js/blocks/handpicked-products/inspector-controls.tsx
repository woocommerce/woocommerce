/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import ProductOrderbyControl from '@woocommerce/editor-components/product-orderby-control';
import ProductsControl from '@woocommerce/editor-components/products-control';

/**
 * Internal dependencies
 */
import { Props } from './types';

export const HandpickedProductsInspectorControls = (
	props: Props
): JSX.Element => {
	const { attributes, setAttributes } = props;
	const { columns, contentVisibility, orderby, alignButtons } = attributes;

	return (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Layout', 'woocommerce' ) } initialOpen>
				<RangeControl
					label={ __( 'Columns', 'woocommerce' ) }
					value={ columns }
					onChange={ ( value ) =>
						setAttributes( { columns: value } )
					}
					min={ getSetting( 'minColumns', 1 ) }
					max={ getSetting( 'maxColumns', 6 ) }
				/>
				<ToggleControl
					label={ __( 'Align Buttons', 'woocommerce' ) }
					help={
						alignButtons
							? __(
									'Buttons are aligned vertically.',
									'woocommerce'
							  )
							: __( 'Buttons follow content.', 'woocommerce' )
					}
					checked={ alignButtons }
					onChange={ () =>
						setAttributes( { alignButtons: ! alignButtons } )
					}
				/>
			</PanelBody>
			<PanelBody title={ __( 'Content', 'woocommerce' ) } initialOpen>
				<GridContentControl
					settings={ contentVisibility }
					onChange={ ( value ) =>
						setAttributes( { contentVisibility: value } )
					}
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Order By', 'woocommerce' ) }
				initialOpen={ false }
			>
				<ProductOrderbyControl
					setAttributes={ setAttributes }
					value={ orderby }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Products', 'woocommerce' ) }
				initialOpen={ false }
			>
				<ProductsControl
					selected={ attributes.products }
					onChange={ ( value = [] ) => {
						const ids = value.map( ( { id } ) => id );
						setAttributes( { products: ids } );
					} }
					isCompact={ true }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
