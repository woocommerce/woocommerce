/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Panel,
	PanelBody,
	PanelHeader,
	TextControl,
} from '@wordpress/components';
import { closeSmall, cog } from '@wordpress/icons';
import { Product } from '@woocommerce/data';
import { useFormContext2 } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../sections/utils';
import { WooHeaderItem } from '~/header/utils';
import './product-settings.scss';

export const ProductSettings = () => {
	const { control } = useFormContext2< Product >();
	const [ isOpen, setIsOpen ] = useState( false );

	const { field: reviewsField } = useController( {
		name: 'reviews_allowed',
		control,
	} );

	const { field: menuField } = useController( {
		name: 'menu_order',
		control,
	} );

	return (
		<WooHeaderItem>
			<>
				<Button
					aria-label={ __( 'Settings', 'woocommerce' ) }
					icon={ cog }
					isPressed={ isOpen }
					onClick={ () => setIsOpen( ! isOpen ) }
					className="woocommerce-product-settings__toggle"
				/>
				{ isOpen && (
					<Panel className="woocommerce-product-settings__panel">
						<PanelHeader label={ __( 'Settings', 'woocommerce' ) }>
							<Button
								icon={ closeSmall }
								onClick={ () => setIsOpen( false ) }
								aria-label={ __(
									'Close settings',
									'woocommerce'
								) }
							/>
						</PanelHeader>
						<PanelBody title={ __( 'Advanced', 'woocommerce' ) }>
							<CheckboxControl
								label={ __( 'Enable reviews', 'woocommerce' ) }
								onChange={ ( value ) => {
									reviewsField.onChange( value );
									getCheckboxTracks(
										'reviews_allowed'
									).onChange( value );
								} }
								checked={ reviewsField.value }
							/>
							<TextControl
								label={ __( 'Menu order', 'woocommerce' ) }
								type="number"
								value={ menuField.value }
								onChange={ menuField.onChange }
							/>
						</PanelBody>
					</Panel>
				) }
			</>
		</WooHeaderItem>
	);
};
