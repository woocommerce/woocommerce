/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	useBlockProps,
} from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';
import { Attributes } from './types';
import './editor.scss';

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

const TitleEdit = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const blockProps = useBlockProps();
	const { headingLevel, showProductLink, align } = attributes;
	return (
		<div { ...blockProps }>
			<BlockControls>
				<HeadingToolbar
					isCollapsed={ true }
					minLevel={ 1 }
					maxLevel={ 7 }
					selectedLevel={ headingLevel }
					onChange={ ( newLevel: number ) =>
						setAttributes( { headingLevel: newLevel } )
					}
				/>
				{ isFeaturePluginBuild() && (
					<AlignmentToolbar
						value={ align }
						onChange={ ( newAlign ) => {
							setAttributes( { align: newAlign } );
						} }
					/>
				) }
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				>
					<ToggleControl
						label={ __(
							'Link to Product Page',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Links the image to the single product listing.',
							'woo-gutenberg-products-block'
						) }
						checked={ showProductLink }
						onChange={ () =>
							setAttributes( {
								showProductLink: ! showProductLink,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</div>
	);
};

const Title = isFeaturePluginBuild()
	? compose( [
			withProductSelector( {
				icon: BLOCK_ICON,
				label: BLOCK_TITLE,
				description: __(
					'Choose a product to display its title.',
					'woo-gutenberg-products-block'
				),
			} ),
	  ] )( TitleEdit )
	: TitleEdit;

export default Title;
