/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { AttributeTerm } from '@woocommerce/types';
import { useState } from '@wordpress/element';
import {
	PanelBody,
	ColorPicker,
	ColorIndicator,
	FlexItem,
	Popover,
	__experimentalHStack as HStack,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { EditProps } from './types';
import './style.scss';
import './editor.scss';

const Edit = ( { attributes, setAttributes, context }: EditProps ) => {
	const [ editingTerm, setEditingTerm ] = useState< number >( 0 );

	const { termColors, displayStyle } = attributes;
	const { filterOptions, attributeTerms } = context;

	const blockProps = useBlockProps( {
		className: `style-${ displayStyle }`,
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					className="wc-block-attribute-color-setting"
					title={ __( 'Color Setting', 'woocommerce' ) }
				>
					{ attributeTerms?.map( ( term: AttributeTerm ) => (
						<HStack justify="flex-start" key={ term.id }>
							<ColorIndicator
								colorValue={
									termColors[ term.id ] ?? '#eeeeee'
								}
								onClick={ () => setEditingTerm( term.id ) }
							/>
							<FlexItem title={ term.name }>
								{ term.name }
							</FlexItem>
							{ editingTerm === term.id && (
								<Popover
									onFocusOutside={ () => setEditingTerm( 0 ) }
								>
									<ColorPicker
										color={
											termColors[ editingTerm ] ?? ''
										}
										onChangeComplete={ ( color ) => {
											setAttributes( {
												termColors: {
													...termColors,
													[ editingTerm ]: color.hex,
												},
											} );
										} }
									/>
								</Popover>
							) }
						</HStack>
					) ) }
				</PanelBody>
				<PanelBody
					className="wc-block-attribute-display-setting"
					title={ __( 'Display Setting', 'woocommerce' ) }
				>
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
							value="grid"
							label={ __( 'Grid', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div className="terms">
				{ filterOptions.map( ( option ) => (
					<div className="term" key={ option.id }>
						<span
							className="color"
							style={ {
								backgroundColor:
									option?.attrs?.id in termColors
										? termColors[
												option?.attrs?.id as number
										  ]
										: '#eee',
							} }
						></span>
						<span className="name">{ option.label }</span>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default Edit;
