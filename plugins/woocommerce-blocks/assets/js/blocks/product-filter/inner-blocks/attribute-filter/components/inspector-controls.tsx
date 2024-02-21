/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { getBlockTypes, createBlock } from '@wordpress/blocks';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
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
import { AttributeSelectControls } from './attribute-select-controls';
import { BlockAttributes, StyleOption } from '../types';

export const Inspector = ( {
	clientId,
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) => {
	const { attributeId, showCounts, queryType, displayStyle, selectType } =
		attributes;

	const [ styleOptions, setStyleOptions ] = useState< StyleOption[] >();
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		setStyleOptions(
			getBlockTypes()
				.filter( ( block ) =>
					block?.ancestor?.includes(
						'woocommerce/product-filter-attribute'
					)
				)
				.map( ( block ) => ( {
					label: block.title,
					value: block.name,
				} ) )
		);
	}, [] );

	return (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Display Settings', 'woocommerce' ) }>
				<ToggleControl
					label={ __( 'Display product count', 'woocommerce' ) }
					checked={ showCounts }
					onChange={ () =>
						setAttributes( {
							showCounts: ! showCounts,
						} )
					}
				/>
				<ToggleGroupControl
					label={ __(
						'Allow selecting multiple options?',
						'woocommerce'
					) }
					value={ selectType || 'multiple' }
					onChange={ ( value: string ) =>
						setAttributes( {
							selectType: value,
						} )
					}
					className="wc-block-attribute-filter__multiple-toggle"
				>
					<ToggleGroupControlOption
						value="multiple"
						label={ __( 'Multiple', 'woocommerce' ) }
					/>
					<ToggleGroupControlOption
						value="single"
						label={ __( 'Single', 'woocommerce' ) }
					/>
				</ToggleGroupControl>
				{ selectType === 'multiple' && (
					<ToggleGroupControl
						label={ __( 'Filter Conditions', 'woocommerce' ) }
						help={
							queryType === 'and'
								? __(
										'Choose to return filter results for all of the attributes selected.',
										'woocommerce'
								  )
								: __(
										'Choose to return filter results for any of the attributes selected.',
										'woocommerce'
								  )
						}
						value={ queryType }
						onChange={ ( value: string ) =>
							setAttributes( {
								queryType: value,
							} )
						}
						className="wc-block-attribute-filter__conditions-toggle"
					>
						<ToggleGroupControlOption
							value="and"
							label={ __( 'All', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="or"
							label={ __( 'Any', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				) }
				<SelectControl
					label={ __(
						'Display Style',
						'woo-gutenberg-products-block'
					) }
					value={ displayStyle }
					options={ styleOptions }
					onChange={ ( displayStyle ) => {
						setAttributes( {
							displayStyle,
						} );
						replaceInnerBlocks( clientId, [
							createBlock( displayStyle ),
						] );
					} }
				></SelectControl>
			</PanelBody>
			<PanelBody
				title={ __( 'Content Settings', 'woocommerce' ) }
				initialOpen={ false }
			>
				<AttributeSelectControls
					isCompact={ true }
					attributeId={ attributeId }
					setAttributes={ setAttributes }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
