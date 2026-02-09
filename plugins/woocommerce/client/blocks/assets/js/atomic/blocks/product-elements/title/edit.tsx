/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	useBlockProps,
} from '@wordpress/block-editor';
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';
import {
	Disabled,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import { Attributes } from './types';
import './editor.scss';

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

const DEFAULT_ATTRIBUTES = {
	showProductLink: true,
	linkTarget: '_self',
};

const TitleEdit = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const blockProps = useBlockProps();
	const { headingLevel, showProductLink, align, linkTarget } = attributes;
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
				<AlignmentToolbar
					value={ align }
					onChange={ ( newAlign ) => {
						setAttributes( { align: newAlign } );
					} }
				/>
			</BlockControls>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Link settings', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							showProductLink: DEFAULT_ATTRIBUTES.showProductLink,
							linkTarget: DEFAULT_ATTRIBUTES.linkTarget,
						} )
					}
				>
					<ToolsPanelItem
						label={ __( 'Make title a link', 'woocommerce' ) }
						hasValue={ () =>
							showProductLink !==
							DEFAULT_ATTRIBUTES.showProductLink
						}
						onDeselect={ () =>
							setAttributes( {
								showProductLink:
									DEFAULT_ATTRIBUTES.showProductLink,
							} )
						}
						isShownByDefault
					>
						<ToggleControl
							label={ __( 'Make title a link', 'woocommerce' ) }
							checked={ showProductLink }
							onChange={ () =>
								setAttributes( {
									showProductLink: ! showProductLink,
								} )
							}
						/>
					</ToolsPanelItem>
					{ showProductLink && (
						<ToolsPanelItem
							label={ __( 'Open in new tab', 'woocommerce' ) }
							hasValue={ () =>
								linkTarget !== DEFAULT_ATTRIBUTES.linkTarget
							}
							onDeselect={ () =>
								setAttributes( {
									linkTarget: DEFAULT_ATTRIBUTES.linkTarget,
								} )
							}
							isShownByDefault
						>
							<ToggleControl
								label={ __( 'Open in new tab', 'woocommerce' ) }
								onChange={ ( value ) =>
									setAttributes( {
										linkTarget: value ? '_blank' : '_self',
									} )
								}
								checked={ linkTarget === '_blank' }
							/>
						</ToolsPanelItem>
					) }
				</ToolsPanel>
			</InspectorControls>
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</div>
	);
};

export default TitleEdit;
