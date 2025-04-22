/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Link } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useInstanceId } from '@wordpress/compose';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useCurrencyInputProps } from '../../../hooks/use-currency-input-props';
import { PricingBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { Label } from '../../../components/label/label';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< PricingBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const {
		property,
		label = __( 'Price', 'woocommerce' ),
		help,
		disabled,
		tooltip,
	} = attributes;
	const [ price, setPrice ] = useProductEntityProp< string >( property, {
		postType,
		fallbackValue: '',
	} );
	const inputProps = useCurrencyInputProps( {
		value: price || '',
		onChange: setPrice,
	} );

	const interpolatedHelp = help
		? createInterpolateElement( help, {
				PricingTab: (
					<Link
						href={ getNewPath( { tab: 'pricing' } ) }
						onClick={ () => {
							recordEvent( 'product_pricing_help_click' );
						} }
					/>
				),
		  } )
		: null;

	const priceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-pricing-field'
	) as string;

	return (
		<div { ...blockProps }>
			<BaseControl id={ priceId } help={ interpolatedHelp }>
				<InputControl
					{ ...inputProps }
					disabled={ disabled }
					id={ priceId }
					name={ property }
					label={
						tooltip ? (
							<Label label={ label } tooltip={ tooltip } />
						) : (
							label
						)
					}
				/>
			</BaseControl>
		</div>
	);
}
