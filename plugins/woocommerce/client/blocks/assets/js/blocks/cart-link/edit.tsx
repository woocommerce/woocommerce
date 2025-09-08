/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	PlainText,
} from '@wordpress/block-editor';
import { cartOutline, bag, bagAlt } from '@woocommerce/icons';
import { __ } from '@wordpress/i18n';
import {
	Icon,
	PanelBody,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import QuantityBadge from '../mini-cart/quantity-badge';

export interface Attributes {
	cartIcon: 'cart' | 'bag' | 'bag-alt';
	content: string;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

const Edit = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { cartIcon, content } = attributes;

	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleGroupControl
						className="wc-block-editor-mini-cart__cart-icon-toggle"
						isBlock
						label={ __( 'Cart Icon', 'woocommerce' ) }
						value={ cartIcon }
						onChange={ ( value: 'cart' | 'bag' | 'bag-alt' ) => {
							setAttributes( {
								cartIcon: value,
							} );
						} }
					>
						<ToggleGroupControlOption
							value={ 'cart' }
							label={ <Icon size={ 32 } icon={ cartOutline } /> }
						/>
						<ToggleGroupControlOption
							value={ 'bag' }
							label={ <Icon size={ 32 } icon={ bag } /> }
						/>
						<ToggleGroupControlOption
							value={ 'bag-alt' }
							label={ <Icon size={ 32 } icon={ bagAlt } /> }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<a
				className="wc-block-cart-link"
				href={ '#cart-pseudo-link' }
				onClick={ ( event ) => event.preventDefault() }
			>
				<QuantityBadge
					icon={ cartIcon }
					productCountVisibility={ 'never' }
				/>
				<PlainText
					className="wc-block-cart-link__text"
					value={
						content !== null ? content : __( 'Cart', 'woocommerce' )
					}
					__experimentalVersion={ 2 }
					onChange={ ( value: string ) =>
						setAttributes( {
							content: value,
						} )
					}
					style={ { backgroundColor: 'transparent', resize: 'none' } }
				/>
			</a>
		</div>
	);
};

export default Edit;
