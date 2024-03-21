/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { ADMIN_URL, getSetting } from '@woocommerce/settings';
import { CHECKOUT_PAGE_ID } from '@woocommerce/block-settings';
import {
	CheckboxControl,
	SelectControl,
	TextControl,
	ExternalLink,
	Notice,
} from '@wordpress/components';
import styled from '@emotion/styled';

/**
 * Internal dependencies
 */
import { SettingsCard, SettingsSection } from '../shared-components';
import { useSettingsContext } from './settings-context';

const GeneralSettingsDescription = () => (
	<>
		<h2>{ __( 'General', 'woocommerce' ) }</h2>
		<p>
			{ __(
				'Enable or disable local pickup on your store, and define costs. Local pickup is only available from the block checkout.',
				'woocommerce'
			) }
		</p>
		<ExternalLink
			href={ `${ ADMIN_URL }post.php?post=${ CHECKOUT_PAGE_ID }&action=edit` }
		>
			{ __( 'View checkout page', 'woocommerce' ) }
		</ExternalLink>
	</>
);

const StyledNotice = styled( Notice )`
	margin-left: 0;
	margin-right: 0;
`;

const GeneralSettings = () => {
	const { settings, setSettingField, readOnlySettings } =
		useSettingsContext();
	const [ showCosts, setShowCosts ] = useState( !! settings.cost );

	const shippingCostRequiresAddress = getSetting< boolean >(
		'shippingCostRequiresAddress',
		false
	);

	return (
		<SettingsSection Description={ GeneralSettingsDescription }>
			<SettingsCard>
				{ readOnlySettings.hasLegacyPickup && (
					<StyledNotice status="warning" isDismissible={ false }>
						{ createInterpolateElement(
							__(
								"By enabling Local Pickup with more valuable features for your store, it's recommended that you remove the legacy Local Pickup option from your <a>shipping zones</a>.",
								'woocommerce'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a
										href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping` }
									/>
								),
							}
						) }
					</StyledNotice>
				) }
				<CheckboxControl
					checked={ settings.enabled }
					name="local_pickup_enabled"
					onChange={ setSettingField( 'enabled' ) }
					label={ __( 'Enable local pickup', 'woocommerce' ) }
					help={
						<span>
							{ __(
								'When enabled, local pickup will appear as an option on the block based checkout.',
								'woocommerce'
							) }
							{ shippingCostRequiresAddress ? (
								<>
									<br />
									{ __(
										'If local pickup is enabled, the "Hide shipping costs until an address is entered" setting will be ignored.',
										'woocommerce'
									) }
								</>
							) : null }
						</span>
					}
				/>
				<TextControl
					label={ __( 'Title', 'woocommerce' ) }
					name="local_pickup_title"
					help={ __(
						'This is the shipping method title shown to customers.',
						'woocommerce'
					) }
					placeholder={ __( 'Local Pickup', 'woocommerce' ) }
					value={ settings.title }
					onChange={ setSettingField( 'title' ) }
					disabled={ false }
					autoComplete="off"
					required={ true }
					onInvalid={ (
						event: React.InvalidEvent< HTMLInputElement >
					) => {
						event.target.setCustomValidity(
							__(
								'Local pickup title is required',
								'woocommerce'
							)
						);
					} }
					onInput={ (
						event: React.ChangeEvent< HTMLInputElement >
					) => {
						event.target.setCustomValidity( '' );
					} }
				/>
				<CheckboxControl
					checked={ showCosts }
					onChange={ () => {
						setShowCosts( ! showCosts );
						setSettingField( 'cost' )( '' );
					} }
					label={ __(
						'Add a price for customers who choose local pickup',
						'woocommerce'
					) }
					help={ __(
						'By default, the local pickup shipping method is free.',
						'woocommerce'
					) }
				/>
				{ showCosts ? (
					<>
						<TextControl
							label={ __( 'Cost', 'woocommerce' ) }
							name="local_pickup_cost"
							help={ __(
								'Optional cost to charge for local pickup.',
								'woocommerce'
							) }
							placeholder={ __( 'Free', 'woocommerce' ) }
							type="number"
							pattern="[0-9]+\.?[0-9]*"
							min={ 0 }
							value={ settings.cost }
							onChange={ setSettingField( 'cost' ) }
							disabled={ false }
							autoComplete="off"
						/>
						<SelectControl
							label={ __( 'Taxes', 'woocommerce' ) }
							name="local_pickup_tax_status"
							help={ __(
								'If a cost is defined, this controls if taxes are applied to that cost.',
								'woocommerce'
							) }
							options={ [
								{
									label: __( 'Taxable', 'woocommerce' ),
									value: 'taxable',
								},
								{
									label: __( 'Not taxable', 'woocommerce' ),
									value: 'none',
								},
							] }
							value={ settings.tax_status }
							onChange={ setSettingField( 'tax_status' ) }
							disabled={ false }
						/>
					</>
				) : null }
			</SettingsCard>
		</SettingsSection>
	);
};

export default GeneralSettings;
