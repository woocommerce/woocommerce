/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { Disabled, PanelBody, withSpokenMessages } from '@wordpress/components';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';
import BlockTitle from '@woocommerce/block-components/block-title';

/**
 * Internal dependencies
 */
import Block from './block.js';
import ToggleButtonControl from '../../components/toggle-button-control';

const Edit = ( { attributes, setAttributes } ) => {
	const { className, displayStyle, heading, headingLevel } = attributes;

	const getInspectorControls = () => {
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
								/* translators: "Chips" is a tag-like display style for chosen attributes. */
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
						selectedLevel={ headingLevel }
						onChange={ ( newLevel ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	return (
		<div className={ className }>
			{ getInspectorControls() }
			<BlockTitle
				headingLevel={ headingLevel }
				heading={ heading }
				onChange={ ( value ) => setAttributes( { heading: value } ) }
			/>
			<Disabled>
				<Block attributes={ attributes } isEditor={ true } />
			</Disabled>
		</div>
	);
};

export default withSpokenMessages( Edit );
