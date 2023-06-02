/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { Disabled, PanelBody, withSpokenMessages } from '@wordpress/components';
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';
import BlockTitle from '@woocommerce/editor-components/block-title';
import ToggleButtonControl from '@woocommerce/editor-components/toggle-button-control';

/**
 * Internal dependencies
 */
import Block from './block.js';

const Edit = ( { attributes, setAttributes } ) => {
	const { className, displayStyle, heading, headingLevel } = attributes;

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __(
						'Block Settings',
						'woocommerce'
					) }
				>
					<ToggleButtonControl
						label={ __(
							'Display Style',
							'woocommerce'
						) }
						value={ displayStyle }
						options={ [
							{
								label: __(
									'List',
									'woocommerce'
								),
								value: 'list',
							},
							{
								/* translators: "Chips" is a tag-like display style for chosen attributes. */
								label: __(
									'Chips',
									'woocommerce'
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
							'woocommerce'
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
