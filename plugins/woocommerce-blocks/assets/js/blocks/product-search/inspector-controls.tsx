/**
 * External dependencies
 */
import { type ElementType, useEffect, useState } from '@wordpress/element';
import { EditorBlock } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RadioControl,
	ToggleControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControlOption` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { isWooSearchBlockVariation } from './utils';
import { ButtonPositionProps, ProductSearchBlock } from './types';
import { ButtonOptions } from './constants';

const ProductSearchControls = ( props: ProductSearchBlock ) => {
	const { attributes, setAttributes } = props;
	const { buttonPosition, buttonUseIcon, showLabel } = attributes;
	const [ initialPosition, setInitialPosition ] =
		useState< ButtonPositionProps >( buttonPosition );

	const isInputAndButtonOption =
		buttonPosition === 'button-outside' ||
		buttonPosition === 'button-inside';

	useEffect( () => {
		if ( isInputAndButtonOption && initialPosition !== buttonPosition ) {
			setInitialPosition( buttonPosition );
		}
	}, [ buttonPosition ] );

	function getSelectedRadioControlOption() {
		if ( isInputAndButtonOption ) {
			return ButtonOptions.INPUT_AND_BUTTON;
		}
		return buttonPosition;
	}

	return (
		<>
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Styles', 'woocommerce' ) }>
					<RadioControl
						selected={ getSelectedRadioControlOption() }
						options={ [
							{
								label: __( 'Input and button', 'woocommerce' ),
								value: ButtonOptions.INPUT_AND_BUTTON,
							},
							{
								label: __( 'Input only', 'woocommerce' ),
								value: ButtonOptions.NO_BUTTON,
							},
							{
								label: __( 'Button only', 'woocommerce' ),
								value: ButtonOptions.BUTTON_ONLY,
							},
						] }
						onChange={ (
							selected: Partial< ButtonPositionProps > &
								'input-and-button'
						) => {
							if ( selected !== ButtonOptions.INPUT_AND_BUTTON ) {
								setAttributes( {
									buttonPosition: selected,
								} );
							} else {
								setAttributes( {
									buttonPosition:
										selected ===
										ButtonOptions.INPUT_AND_BUTTON
											? initialPosition
											: selected,
								} );
							}
						} }
					/>
					{ buttonPosition !== ButtonOptions.NO_BUTTON && (
						<>
							<ToggleGroupControl
								label={ __( 'BUTTON POSITION', 'woocommerce' ) }
								isBlock
								onChange={ ( value: ButtonPositionProps ) => {
									setAttributes( {
										buttonPosition: value,
									} );
								} }
								value={ ButtonOptions.INSIDE }
							>
								<ToggleGroupControlOption
									value={ ButtonOptions.INSIDE }
									label={ __( 'Inside', 'woocommerce' ) }
								/>
								<ToggleGroupControlOption
									value={ ButtonOptions.OUTSIDE }
									label={ __( 'Outside', 'woocommerce' ) }
								/>
							</ToggleGroupControl>
							<ToggleGroupControl
								label={ __(
									'BUTTON APPEARANCE',
									'woocommerce'
								) }
								isBlock
								onChange={ ( value: boolean ) => {
									setAttributes( {
										buttonUseIcon: value,
									} );
								} }
								value={ buttonUseIcon }
							>
								<ToggleGroupControlOption
									value={ false }
									label={ __( 'Text', 'woocommerce' ) }
								/>
								<ToggleGroupControlOption
									value={ true }
									label={ __( 'Icon', 'woocommerce' ) }
								/>
							</ToggleGroupControl>
						</>
					) }
					<ToggleControl
						label={ __( 'Show input label', 'woocommerce' ) }
						checked={ showLabel }
						onChange={ ( showInputLabel: boolean ) =>
							setAttributes( {
								showLabel: showInputLabel,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

export const withProductSearchControls =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props: ProductSearchBlock ) => {
		return isWooSearchBlockVariation( props ) ? (
			<>
				<ProductSearchControls { ...props } />
				<BlockEdit { ...props } />
			</>
		) : (
			<BlockEdit { ...props } />
		);
	};
