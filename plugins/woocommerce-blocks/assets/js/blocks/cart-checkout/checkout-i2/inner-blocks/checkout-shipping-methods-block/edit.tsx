/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, Disabled, ExternalLink } from '@wordpress/components';
import { ADMIN_URL, getSetting } from '@woocommerce/settings';
import ExternalLinkCard from '@woocommerce/editor-components/external-link-card';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import Block from './block';

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
		allowCreateAccount: boolean;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const globalShippingMethods = getSetting(
		'globalShippingMethods'
	) as shippingAdminLink[];
	const activeShippingZones = getSetting(
		'activeShippingZones'
	) as shippingAdminLink[];

	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
		>
			<InspectorControls>
				{ globalShippingMethods.length > 0 && (
					<PanelBody
						title={ __(
							'Methods',
							'woo-gutenberg-products-block'
						) }
					>
						<p className="wc-block-checkout__controls-text">
							{ __(
								'You currently have the following shipping integrations active.',
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
						title={ __( 'Zones', 'woo-gutenberg-products-block' ) }
					>
						<p className="wc-block-checkout__controls-text">
							{ __(
								'You currently have the following shipping zones active.',
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
						<ExternalLink
							href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping` }
						>
							{ __(
								'Manage shipping zones',
								'woo-gutenberg-products-block'
							) }
						</ExternalLink>
					</PanelBody>
				) }
			</InspectorControls>
			<Disabled>
				<Block />
			</Disabled>
			<AdditionalFields area="shippingMethods" />
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
