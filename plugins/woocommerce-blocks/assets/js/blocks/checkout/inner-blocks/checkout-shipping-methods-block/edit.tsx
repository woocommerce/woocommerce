/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ExternalLink } from '@wordpress/components';
import { ADMIN_URL, getSetting } from '@woocommerce/settings';
import ExternalLinkCard from '@woocommerce/editor-components/external-link-card';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import NoShippingPlaceholder from './no-shipping-placeholder';
import Block from './block';
import './editor.scss';

type shippingAdminLink = {
	id: number;
	title: string;
	description: string;
};

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		title: string;
		description: string;
		showStepNumber: boolean;
		className: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element | null => {
	const globalShippingMethods = getSetting(
		'globalShippingMethods'
	) as shippingAdminLink[];
	const activeShippingZones = getSetting(
		'activeShippingZones'
	) as shippingAdminLink[];

	const { showShippingMethods } = useCheckoutAddress();

	if ( ! showShippingMethods ) {
		return null;
	}

	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
			className={ classnames(
				'wc-block-checkout__shipping-option',
				attributes?.className
			) }
		>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Shipping Calculations',
						'woo-gutenberg-products-block'
					) }
				>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Options that control shipping can be managed in your store settings.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<ExternalLink
						href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping&section=options` }
					>
						{ __(
							'Manage shipping options',
							'woo-gutenberg-products-block'
						) }
					</ExternalLink>{ ' ' }
				</PanelBody>
				{ globalShippingMethods.length > 0 && (
					<PanelBody
						title={ __(
							'Methods',
							'woo-gutenberg-products-block'
						) }
					>
						<p className="wc-block-checkout__controls-text">
							{ __(
								'The following shipping integrations are active on your store.',
								'woo-gutenberg-products-block'
							) }
						</p>
						{ globalShippingMethods.map( ( method ) => {
							return (
								<ExternalLinkCard
									key={ method.id }
									href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping&section=${ method.id }` }
									title={ method.title }
									description={ method.description }
								/>
							);
						} ) }
						<ExternalLink
							href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping` }
						>
							{ __(
								'Manage shipping methods',
								'woo-gutenberg-products-block'
							) }
						</ExternalLink>
					</PanelBody>
				) }
				{ activeShippingZones.length && (
					<PanelBody
						title={ __(
							'Shipping Zones',
							'woo-gutenberg-products-block'
						) }
					>
						<p className="wc-block-checkout__controls-text">
							{ __(
								'Shipping Zones can be made managed in your store settings.',
								'woo-gutenberg-products-block'
							) }
						</p>
						{ activeShippingZones.map( ( zone ) => {
							return (
								<ExternalLinkCard
									key={ zone.id }
									href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping&zone_id=${ zone.id }` }
									title={ zone.title }
									description={ zone.description }
								/>
							);
						} ) }
					</PanelBody>
				) }
			</InspectorControls>
			<Noninteractive>
				<Block noShippingPlaceholder={ <NoShippingPlaceholder /> } />
			</Noninteractive>
			<AdditionalFields block={ innerBlockAreas.SHIPPING_METHODS } />
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<AdditionalFieldsContent />
		</div>
	);
};
