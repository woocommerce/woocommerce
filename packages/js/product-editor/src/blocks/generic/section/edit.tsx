/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { PanelBody, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SectionBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { SectionHeader } from '../../../components/section-header';

export function SectionBlockEdit( {
	attributes,
	clientId,
	setAttributes,
}: ProductEditorBlockEditProps< SectionBlockAttributes > ) {
	const {
		description = __( 'Section description', 'woocommerce' ),
		title = __( 'Section title', 'woocommerce' ),
		blockGap,
	} = attributes;

	const blockProps = useWooBlockProps( attributes );
	const { hasInnerBlocks } = useSelect(
		( select ) => {
			const { getBlock } = select( blockEditorStore );
			const block = getBlock( clientId );
			return {
				hasInnerBlocks: !! ( block && block.innerBlocks.length ),
			};
		},
		[ clientId ]
	);

	const innerBlocksProps = useInnerBlocksProps(
		{
			...blockProps,
			className: classNames(
				'wp-block-woocommerce-product-section-header__content',
				`wp-block-woocommerce-product-section-header__content--block-gap-${ blockGap }`
			),
		},
		{
			renderAppender: hasInnerBlocks
				? undefined
				: InnerBlocks.ButtonBlockAppender,
		}
	);
	const SectionTagName = title ? 'fieldset' : 'div';

	return (
		<SectionTagName { ...blockProps }>
			{ title && (
				<SectionHeader
					description={ description }
					sectionTagName={ SectionTagName }
					title={ title }
				/>
			) }

			<div { ...innerBlocksProps } />

			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<TextControl
						label={ __( 'Title', 'woocommerce' ) }
						value={ title }
						onChange={ ( newValue ) =>
							setAttributes( { title: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Description', 'woocommerce' ) }
						value={ description }
						onChange={ ( newValue ) =>
							setAttributes( { description: newValue } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</SectionTagName>
	);
}
