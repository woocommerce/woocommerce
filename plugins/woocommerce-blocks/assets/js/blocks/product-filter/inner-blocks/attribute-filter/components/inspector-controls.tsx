/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
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
import { AttributeSelectControls } from './attribute-select-controls';
import { EditProps } from '../types';

export const Inspector = ( { attributes, setAttributes }: EditProps ) => {
	const { attributeId, showCounts, queryType, displayStyle, selectType } =
		attributes;

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
						label={ _x(
							'Multiple',
							'Number of filters',
							'woocommerce'
						) }
					/>
					<ToggleGroupControlOption
						value="single"
						label={ _x(
							'Single',
							'Number of filters',
							'woocommerce'
						) }
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
				<ToggleGroupControl
					label={ __( 'Display Style', 'woocommerce' ) }
					value={ displayStyle }
					onChange={ ( value: string ) =>
						setAttributes( {
							displayStyle: value,
						} )
					}
					className="wc-block-attribute-filter__display-toggle"
				>
					<ToggleGroupControlOption
						value="list"
						label={ __( 'List', 'woocommerce' ) }
					/>
					<ToggleGroupControlOption
						value="dropdown"
						label={ __( 'Dropdown', 'woocommerce' ) }
					/>
				</ToggleGroupControl>
			</PanelBody>
			<PanelBody
				title={ __( 'Content Settings', 'woocommerce' ) }
				initialOpen={ false }
			>
				<AttributeSelectControls
					isCompact={ true }
					attributeId={ attributeId }
					setAttributeId={ ( id: number ) => {
						setAttributes( {
							attributeId: id,
						} );
					} }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
