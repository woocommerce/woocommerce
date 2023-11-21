/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
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
			<PanelBody
				title={ __(
					'Display Settings',
					'woo-gutenberg-products-block'
				) }
			>
				<ToggleControl
					label={ __(
						'Display product count',
						'woo-gutenberg-products-block'
					) }
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
						'woo-gutenberg-products-block'
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
						label={ __(
							'Multiple',
							'woo-gutenberg-products-block'
						) }
					/>
					<ToggleGroupControlOption
						value="single"
						label={ __( 'Single', 'woo-gutenberg-products-block' ) }
					/>
				</ToggleGroupControl>
				{ selectType === 'multiple' && (
					<ToggleGroupControl
						label={ __(
							'Filter Conditions',
							'woo-gutenberg-products-block'
						) }
						help={
							queryType === 'and'
								? __(
										'Choose to return filter results for all of the attributes selected.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Choose to return filter results for any of the attributes selected.',
										'woo-gutenberg-products-block'
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
							label={ __(
								'All',
								'woo-gutenberg-products-block'
							) }
						/>
						<ToggleGroupControlOption
							value="or"
							label={ __(
								'Any',
								'woo-gutenberg-products-block'
							) }
						/>
					</ToggleGroupControl>
				) }
				<ToggleGroupControl
					label={ __(
						'Display Style',
						'woo-gutenberg-products-block'
					) }
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
						label={ __( 'List', 'woo-gutenberg-products-block' ) }
					/>
					<ToggleGroupControlOption
						value="dropdown"
						label={ __(
							'Dropdown',
							'woo-gutenberg-products-block'
						) }
					/>
				</ToggleGroupControl>
			</PanelBody>
			<PanelBody
				title={ __(
					'Content Settings',
					'woo-gutenberg-products-block'
				) }
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
