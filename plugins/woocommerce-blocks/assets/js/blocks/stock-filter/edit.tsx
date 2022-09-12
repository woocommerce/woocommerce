/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	ToggleControl,
	withSpokenMessages,
} from '@wordpress/components';
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';
import BlockTitle from '@woocommerce/editor-components/block-title';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import { Attributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< Attributes > ) => {
	const { className, heading, headingLevel, showCounts, showFilterButton } =
		attributes;

	const blockProps = useBlockProps( {
		className: classnames( 'wc-block-stock-filter', className ),
	} );

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
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
				</PanelBody>
				<PanelBody
					title={ __( 'Settings', 'woo-gutenberg-products-block' ) }
				>
					<ToggleControl
						label={ __(
							"Show 'Apply filters' button",
							'woo-gutenberg-products-block'
						) }
						help={
							showFilterButton
								? __(
										'Products will only update when the button is clicked.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Products will update as soon as attributes are selected.',
										'woo-gutenberg-products-block'
								  )
						}
						checked={ showFilterButton }
						onChange={ ( value ) =>
							setAttributes( {
								showFilterButton: value,
							} )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Typography', 'woo-gutenberg-products-block' ) }
				>
					<p> { __( 'Size', 'woo-gutenberg-products-block' ) } </p>
					<HeadingToolbar
						isCollapsed={ false }
						minLevel={ 2 }
						maxLevel={ 7 }
						selectedLevel={ headingLevel }
						onChange={ ( newLevel: number ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	return (
		<>
			{ getInspectorControls() }
			{
				<div { ...blockProps }>
					<BlockTitle
						className="wc-block-stock-filter__title"
						headingLevel={ headingLevel }
						heading={ heading }
						onChange={ ( value: string ) =>
							setAttributes( { heading: value } )
						}
					/>
					<Disabled>
						<Block attributes={ attributes } isEditor={ true } />
					</Disabled>
				</div>
			}
		</>
	);
};

export default withSpokenMessages( Edit );
