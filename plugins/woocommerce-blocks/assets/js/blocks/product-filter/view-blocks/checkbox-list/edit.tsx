/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';
import FilterElementLabel from '@woocommerce/base-components/filter-element-label';
import { CheckboxList } from '@woocommerce/blocks-components';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { EditProps } from './types';
import './style.scss';
import './editor.scss';

const Edit = ( { attributes, setAttributes, context }: EditProps ) => {
	const { showCounts } = attributes;
	const { filterOptions } = context;

	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					className="wc-block-checkbox-list-display-setting"
					title={ __( 'Display Setting', 'woocommerce' ) }
				>
					<ToggleControl
						label={ __( 'Display product count', 'woocommerce' ) }
						checked={ showCounts }
						onChange={ () =>
							setAttributes( {
								showCounts: ! showCounts,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<CheckboxList
					className="wc-block-attribute-filter style-list"
					onChange={ () => null }
					options={ filterOptions?.map( ( option ) => ( {
						label: (
							<FilterElementLabel
								name={ option.label }
								count={ showCounts ? option.count : null }
							/>
						),
						value: option.value + '',
					} ) ) }
				/>
			</Disabled>
		</div>
	);
};

export default Edit;
