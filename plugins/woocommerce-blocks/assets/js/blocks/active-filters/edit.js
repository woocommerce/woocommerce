/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { InspectorControls, PlainText } from '@wordpress/block-editor';
import { Disabled, PanelBody, withSpokenMessages } from '@wordpress/components';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';

/**
 * Internal dependencies
 */
import Block from './block.js';
import ToggleButtonControl from '../../components/toggle-button-control';

const Edit = ( { attributes, setAttributes } ) => {
	const getInspectorControls = () => {
		const { displayStyle } = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __(
						'Block Settings',
						'woo-gutenberg-products-block'
					) }
				>
					<ToggleButtonControl
						label={ __(
							'Display Style',
							'woo-gutenberg-products-block'
						) }
						value={ displayStyle }
						options={ [
							{
								label: __(
									'List',
									'woo-gutenberg-products-block'
								),
								value: 'list',
							},
							{
								label: __(
									'Chips',
									'woo-gutenberg-products-block'
								),
								value: 'chips',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								displayStyle: value,
							} )
						}
					/>
					<p>
						{ __(
							'Heading Level',
							'woo-gutenberg-products-block'
						) }
					</p>
					<HeadingToolbar
						isCollapsed={ false }
						minLevel={ 2 }
						maxLevel={ 7 }
						selectedLevel={ attributes.headingLevel }
						onChange={ ( newLevel ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	const TagName = `h${ attributes.headingLevel }`;

	return (
		<Fragment>
			{ getInspectorControls() }
			<TagName>
				<PlainText
					className="wc-block-attribute-filter-heading"
					value={ attributes.heading }
					onChange={ ( value ) =>
						setAttributes( { heading: value } )
					}
				/>
			</TagName>
			<Disabled>
				<Block attributes={ attributes } isPreview />
			</Disabled>
		</Fragment>
	);
};

export default withSpokenMessages( Edit );
