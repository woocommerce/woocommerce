/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';

export const Edit = (): JSX.Element => {
	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Block options',
						'woo-gutenberg-products-block'
					) }
				>
					Options for the block go here.
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<Block />
			</Disabled>
		</>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
