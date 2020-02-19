/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import { InspectorControls } from '@wordpress/block-editor';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import PropTypes from 'prop-types';
import { withFeedbackPrompt } from '@woocommerce/block-hocs';
import ViewSwitcher from '@woocommerce/block-components/view-switcher';
import { previewCart } from '@woocommerce/resource-previews';
import { SHIPPING_ENABLED } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';
import EmptyCart from './empty-cart';

/**
 * Component to handle edit mode of "Cart Block".
 */
const CartEditor = ( { className, attributes, setAttributes } ) => {
	const { isShippingCalculatorEnabled, isShippingCostHidden } = attributes;

	const BlockSettings = () => (
		<InspectorControls>
			<PanelBody
				title={ __(
					'Shipping calculations',
					'woo-gutenberg-products-block'
				) }
			>
				<ToggleControl
					label={ __(
						'Shipping calculator',
						'woo-gutenberg-products-block'
					) }
					help={ __(
						'Allow customers to estimate shipping.',
						'woo-gutenberg-products-block'
					) }
					checked={ isShippingCalculatorEnabled }
					onChange={ () =>
						setAttributes( {
							isShippingCalculatorEnabled: ! isShippingCalculatorEnabled,
						} )
					}
				/>
				{ isShippingCalculatorEnabled && (
					<ToggleControl
						label={ __(
							'Hide shipping costs',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Hide shipping costs until an address is entered.',
							'woo-gutenberg-products-block'
						) }
						checked={ isShippingCostHidden }
						onChange={ () =>
							setAttributes( {
								isShippingCostHidden: ! isShippingCostHidden,
							} )
						}
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);

	return (
		<div className={ className }>
			<ViewSwitcher
				label={ __( 'Edit', 'woo-gutenberg-products-block' ) }
				views={ [
					{
						value: 'full',
						name: __( 'Full Cart', 'woo-gutenberg-products-block' ),
					},
					{
						value: 'empty',
						name: __(
							'Empty Cart',
							'woo-gutenberg-products-block'
						),
					},
				] }
				defaultView={ 'full' }
				render={ ( currentView ) => (
					<>
						{ currentView === 'full' && (
							<>
								{ SHIPPING_ENABLED && <BlockSettings /> }
								<Disabled>
									<FullCart
										cartItems={ previewCart.items }
										cartTotals={ previewCart.totals }
										isShippingCostHidden={
											isShippingCostHidden
										}
										isShippingCalculatorEnabled={
											isShippingCalculatorEnabled
										}
									/>
								</Disabled>
							</>
						) }
						<EmptyCart hidden={ currentView === 'full' } />
					</>
				) }
			/>
		</div>
	);
};

CartEditor.propTypes = {
	className: PropTypes.string,
};

export default withFeedbackPrompt(
	__(
		'We are currently working on improving our cart and providing merchants with tools and options to customize their cart to their stores needs.',
		'woo-gutenberg-products-block'
	)
)( CartEditor );
