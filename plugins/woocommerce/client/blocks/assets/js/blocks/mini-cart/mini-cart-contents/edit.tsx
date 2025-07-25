/* eslint-disable jsdoc/check-alignment */
/**
 * External dependencies
 */
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import { EditorProvider } from '@woocommerce/base-context';
import type { TemplateArray } from '@wordpress/blocks';
import type { FocusEvent, ReactElement } from 'react';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useForcedLayout } from '../../cart-checkout-shared';
import { MiniCartInnerBlocksStyle } from './inner-blocks-style';
import './editor.scss';
import { attributes as defaultAttributes } from './attributes';
import { useThemeColors } from '../../../shared/hooks/use-theme-colors';

// Array of allowed block names.
const ALLOWED_BLOCKS = [
	'woocommerce/filled-mini-cart-contents-block',
	'woocommerce/empty-mini-cart-contents-block',
];
const MIN_WIDTH = 300;

interface Props {
	clientId: string;
	attributes: Record< string, unknown >;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: Props ): ReactElement => {
	const { currentView, width } = attributes;

	const blockProps = useBlockProps();

	const defaultTemplate = [
		[ 'woocommerce/filled-mini-cart-contents-block', {}, [] ],
		[ 'woocommerce/empty-mini-cart-contents-block', {}, [] ],
	] as TemplateArray;

	useForcedLayout( {
		clientId,
		registeredBlocks: ALLOWED_BLOCKS,
		defaultTemplate,
	} );

	// Apply the Mini-Cart Contents block base styles based on Site Editor's background and text colors.
	// We need to set `div` in the selector so it has more specificity than the CSS.
	useThemeColors(
		'mini-cart-contents',
		( { editorBackgroundColor, editorColor } ) => `
				div:where(.wp-block-woocommerce-mini-cart-contents) {
					background-color: ${ editorBackgroundColor };
					color: ${ editorColor };
				}
			`
	);

	return (
		<>
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Dimensions', 'woocommerce' ) }
					initialOpen
				>
					<UnitControl
						onChange={ ( value ) => {
							setAttributes( { width: value } );
						} }
						onBlur={ ( e: FocusEvent< HTMLInputElement > ) => {
							if ( e.target.value === '' ) {
								setAttributes( {
									width: defaultAttributes.width.default,
								} );
							} else if ( Number( e.target.value ) < MIN_WIDTH ) {
								setAttributes( {
									width: MIN_WIDTH + 'px',
								} );
							}
						} }
						value={ width }
						units={ [
							{
								value: 'px',
								label: 'px',
								default: defaultAttributes.width.default,
							},
						] }
					/>
				</PanelBody>
			</InspectorControls>

			<div
				className="wc-block-components-drawer__screen-overlay"
				aria-hidden="true"
			></div>
			<div className="wc-block-editor-mini-cart-contents__wrapper">
				<div { ...blockProps }>
					<EditorProvider currentView={ currentView }>
						<InnerBlocks
							allowedBlocks={ ALLOWED_BLOCKS }
							template={ defaultTemplate }
							templateLock={ false }
						/>
					</EditorProvider>
					<MiniCartInnerBlocksStyle style={ blockProps.style } />
				</div>
			</div>
		</>
	);
};

export default Edit;

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
