/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';

/**
 * Internal dependencies
 */
import Block from './block';

export default ( { attributes, setAttributes } ) => {
	const { headingLevel, productLink } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'woocommerce' ) }
				>
					<p>{ __( 'Level', 'woocommerce' ) }</p>
					<HeadingToolbar
						isCollapsed={ false }
						minLevel={ 1 }
						maxLevel={ 7 }
						selectedLevel={ headingLevel }
						onChange={ ( newLevel ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
					<ToggleControl
						label={ __(
							'Link to Product Page',
							'woocommerce'
						) }
						help={ __(
							'Links the image to the single product listing.',
							'woocommerce'
						) }
						checked={ productLink }
						onChange={ () =>
							setAttributes( {
								productLink: ! productLink,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</>
	);
};
