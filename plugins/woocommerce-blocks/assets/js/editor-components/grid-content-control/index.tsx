/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

interface GridContentControlProps {
	onChange: ( settings: GridContentSettings ) => void;
	settings: GridContentSettings;
}

interface GridContentSettings {
	image: boolean;
	button: boolean;
	price: boolean;
	rating: boolean;
	title: boolean;
}
/**
 * A combination of toggle controls for content visibility in product grids.
 *
 * @param {Object}            props          Incoming props for the component.
 * @param {function(any):any} props.onChange
 * @param {Object}            props.settings
 */
const GridContentControl = ( {
	onChange,
	settings,
}: GridContentControlProps ) => {
	const { image, button, price, rating, title } = settings;
	// If `image` is undefined, that might be because it's a block that was
	// created before the `image` attribute existed, so we default to true.
	const imageIsVisible = image !== false;
	return (
		<>
			<ToggleControl
				label={ __( 'Product image', 'woocommerce' ) }
				checked={ imageIsVisible }
				onChange={ () =>
					onChange( { ...settings, image: ! imageIsVisible } )
				}
			/>
			<ToggleControl
				label={ __( 'Product title', 'woocommerce' ) }
				checked={ title }
				onChange={ () => onChange( { ...settings, title: ! title } ) }
			/>
			<ToggleControl
				label={ __( 'Product price', 'woocommerce' ) }
				checked={ price }
				onChange={ () => onChange( { ...settings, price: ! price } ) }
			/>
			<ToggleControl
				label={ __( 'Product rating', 'woocommerce' ) }
				checked={ rating }
				onChange={ () => onChange( { ...settings, rating: ! rating } ) }
			/>
			<ToggleControl
				label={ __( 'Add to Cart button', 'woocommerce' ) }
				checked={ button }
				onChange={ () => onChange( { ...settings, button: ! button } ) }
			/>
		</>
	);
};

export default GridContentControl;
