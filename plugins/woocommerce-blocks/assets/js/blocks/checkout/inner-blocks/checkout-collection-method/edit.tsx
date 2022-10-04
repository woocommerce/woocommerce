/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import Block from './block';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		title: string;
		description: string;
		showStepNumber: boolean;
		allowCreateAccount: boolean;
		showPrice: boolean;
		showIcon: boolean;
		className: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const [ currentView, changeView ] = useState( 'shipping' );
	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
			className={ classnames(
				'wc-block-checkout__collection-method',
				attributes?.className
			) }
		>
			<InspectorControls>
				<PanelBody
					title={ __( 'Appearance', 'woo-gutenberg-products-block' ) }
				>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Choose how this block is displayed to your customers.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<ToggleControl
						label={ __(
							'Show icon',
							'woo-gutenberg-products-block'
						) }
						checked={ attributes.showIcon }
						onChange={ () =>
							setAttributes( {
								showIcon: ! attributes.showIcon,
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Show costs',
							'woo-gutenberg-products-block'
						) }
						checked={ attributes.showPrice }
						onChange={ () =>
							setAttributes( {
								showPrice: ! attributes.showPrice,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Block
				checked={ currentView }
				onChange={ changeView }
				showPrice={ attributes.showPrice }
				showIcon={ attributes.showIcon }
			/>
			<AdditionalFields block={ innerBlockAreas.COLLECTION_METHOD } />
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
