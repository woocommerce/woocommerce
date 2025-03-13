/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import BlockTitle from '@woocommerce/editor-components/block-title';
import {
	Disabled,
	PanelBody,
	withSpokenMessages,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import type { Attributes } from './types';
import './editor.scss';
import { UpgradeNotice } from '../filter-wrapper/upgrade';

const Edit = ( {
	attributes,
	setAttributes,
	clientId,
}: BlockEditProps< Attributes > ) => {
	const { className, displayStyle, heading, headingLevel } = attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody>
					<UpgradeNotice clientId={ clientId } />
				</PanelBody>
				<PanelBody title={ __( 'Display Settings', 'woocommerce' ) }>
					<ToggleGroupControl
						label={ __( 'Display Style', 'woocommerce' ) }
						isBlock
						value={ displayStyle }
						onChange={ ( value: Attributes[ 'displayStyle' ] ) =>
							setAttributes( {
								displayStyle: value,
							} )
						}
						className="wc-block-active-filter__style-toggle"
					>
						<ToggleGroupControlOption
							value="list"
							label={ __( 'List', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="chips"
							label={ __( 'Chips', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
		);
	};

	return (
		<div { ...blockProps }>
			{ getInspectorControls() }
			{ heading && (
				<BlockTitle
					className="wc-block-active-filters__title"
					headingLevel={ headingLevel }
					heading={ heading }
					onChange={ ( value: Attributes[ 'heading' ] ) =>
						setAttributes( { heading: value } )
					}
				/>
			) }
			<Disabled>
				<Block attributes={ attributes } isEditor={ true } />
			</Disabled>
		</div>
	);
};

export default withSpokenMessages( Edit );
