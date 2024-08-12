/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';
import { InspectorControls } from '@wordpress/block-editor';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	ComboboxControl,
	PanelBody,
	SelectControl,
	ToggleControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { sortOrderOptions } from '../constants';
import { BlockAttributes, EditProps } from '../types';
import {
	ProductFilterSettingsControls,
	ProductFilterStylesControls,
} from '../../../components/product-filter-inspector';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

export const Inspector = ( {
	clientId,
	attributes,
	setAttributes,
}: EditProps ) => {
	const {
		attributeId,
		sortOrder,
		queryType,
		displayStyle,
		showCounts,
		hideEmpty,
		clearButton,
	} = attributes;

	const wrapperClientIds = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlockParentsByBlockName(
			clientId,
			'woocommerce/product-filter'
		);
	} );

	const SettingsControls = () => (
		<>
			<PanelBody title={ __( 'Attribute', 'woocommerce' ) }>
				<ComboboxControl
					options={ ATTRIBUTES.map( ( item ) => ( {
						value: item.attribute_id,
						label: item.attribute_label,
					} ) ) }
					value={ attributeId + '' }
					onChange={ ( value ) =>
						setAttributes( {
							attributeId: parseInt( value || '', 10 ),
						} )
					}
					help={ __(
						'Choose the attribute to show in this filter.',
						'woocommerce'
					) }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
				<SelectControl
					label={ __( 'Sort order', 'woocommerce' ) }
					value={ sortOrder }
					options={ [
						{
							value: '',
							label: __( 'Select an option', 'woocommerce' ),
							disabled: true,
						},
						...sortOrderOptions,
					] }
					onChange={ ( value ) =>
						setAttributes( { sortOrder: value } )
					}
					help={ __(
						'Determine the order of filter options.',
						'woocommerce'
					) }
				/>
				<ToggleGroupControl
					label={ __( 'Logic', 'woocommerce' ) }
					value={ queryType }
					onChange={ ( value: BlockAttributes[ 'queryType' ] ) =>
						setAttributes( { queryType: value } )
					}
					style={ { width: '100%' } }
					help={
						queryType === 'and'
							? createInterpolateElement(
									__(
										'Show results for <b>all</b> selected attributes. Displayed products must contain <b>all of them</b> to appear in the results.',
										'woocommerce'
									),
									{
										b: <strong />,
									}
							  )
							: __(
									'Show results for any of the attributes selected (displayed products don’t have to have them all).',
									'woocommerce'
							  )
					}
				>
					<ToggleGroupControlOption
						label={ __( 'Any', 'woocommerce' ) }
						value="or"
					/>
					<ToggleGroupControlOption
						label={ __( 'All', 'woocommerce' ) }
						value="and"
					/>
				</ToggleGroupControl>
			</PanelBody>
		</>
	);

	const StylesControls = () => (
		<PanelBody title={ __( 'Display', 'woocommerce' ) }>
			<ToggleGroupControl
				value={ displayStyle }
				onChange={ ( value: BlockAttributes[ 'displayStyle' ] ) =>
					setAttributes( { displayStyle: value } )
				}
				style={ { width: '100%' } }
			>
				<ToggleGroupControlOption
					label={ __( 'List', 'woocommerce' ) }
					value="list"
				/>
				<ToggleGroupControlOption
					label={ __( 'Chips', 'woocommerce' ) }
					value="chips"
				/>
			</ToggleGroupControl>
			<ToggleControl
				label={ __( 'Product counts', 'woocommerce' ) }
				checked={ showCounts }
				onChange={ ( value ) => setAttributes( { showCounts: value } ) }
			/>
			<ToggleControl
				label={ __( 'Empty filter options', 'woocommerce' ) }
				checked={ ! hideEmpty }
				onChange={ ( value ) =>
					setAttributes( { hideEmpty: ! value } )
				}
			/>
			<ToggleControl
				label={ __( 'Clear button', 'woocommerce' ) }
				checked={ clearButton }
				onChange={ ( value ) =>
					setAttributes( { clearButton: value } )
				}
			/>
		</PanelBody>
	);

	return (
		<>
			{ wrapperClientIds.length === 1 && (
				<>
					<ProductFilterSettingsControls>
						{ ( {
							providedWrapperClientId,
						}: {
							providedWrapperClientId: string;
						} ) => {
							if (
								! providedWrapperClientId ||
								providedWrapperClientId !==
									wrapperClientIds[ 0 ]
							) {
								return null;
							}
							return <SettingsControls />;
						} }
					</ProductFilterSettingsControls>
					<ProductFilterStylesControls>
						{ ( {
							providedWrapperClientId,
						}: {
							providedWrapperClientId: string;
						} ) => {
							if (
								! providedWrapperClientId ||
								providedWrapperClientId !==
									wrapperClientIds[ 0 ]
							) {
								return null;
							}
							return <StylesControls />;
						} }
					</ProductFilterStylesControls>
				</>
			) }
			<InspectorControls key="inspector">
				<SettingsControls />
			</InspectorControls>
			<InspectorControls group="styles">
				<StylesControls />
			</InspectorControls>
		</>
	);
};
