/**
 * External dependencies
 */
import {
	AlignmentControl,
	BlockControls,
	InspectorControls,
	useBlockProps,
	getColorClassName,
} from '@wordpress/block-editor';
import type { ReactElement } from 'react';
import { formatPrice } from '@woocommerce/price-format';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';
import { __ } from '@wordpress/i18n';
import { positionCenter, positionRight, positionLeft } from '@wordpress/icons';
import { PanelBody, ToggleControl } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';

interface Attributes {
	align: string;
	isInitiallyOpen?: boolean;
	transparentButton: boolean;
	backgroundColor?: string;
	textColor?: string;
	style?: Record< string, Record< string, string > >;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

const MiniCartBlock = ( {
	attributes,
	setAttributes,
}: Props ): ReactElement => {
	const {
		transparentButton,
		backgroundColor,
		textColor,
		style,
		align,
	} = attributes;
	const blockProps = useBlockProps( {
		className: classnames( `wc-block-mini-cart align-${ align }`, {
			'is-transparent': transparentButton,
		} ),
	} );

	/**
	 * @todo Replace `getColorClassName` and manual style manipulation with
	 * `useColorProps` once the hook is no longer experimental.
	 */
	const backgroundClass = getColorClassName(
		'background-color',
		backgroundColor
	);
	const textColorClass = getColorClassName( 'color', textColor );

	const colorStyle = {
		backgroundColor: style?.color?.background,
		color: style?.color?.text,
	};

	const colorClassNames = classnames( backgroundClass, textColorClass, {
		'has-background': backgroundClass || style?.color?.background,
		'has-text-color': textColorClass || style?.color?.text,
	} );

	const productCount = 0;
	const productTotal = 0;

	return (
		<div { ...blockProps }>
			<BlockControls>
				<AlignmentControl
					value={ align }
					alignmentControls={ [
						{
							icon: positionLeft,
							title: __(
								'Align button left',
								'woo-gutenberg-products-block'
							),
							align: 'left',
						},
						{
							icon: positionCenter,
							title: __(
								'Align button center',
								'woo-gutenberg-products-block'
							),
							align: 'center',
						},
						{
							icon: positionRight,
							title: __(
								'Align button right',
								'woo-gutenberg-products-block'
							),
							align: 'right',
						},
					] }
					onChange={ ( newAlign: string ) =>
						setAttributes( { align: newAlign } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Button style',
						'woo-gutenberg-products-block'
					) }
				>
					<ToggleControl
						label={ __(
							'Use transparent button',
							'woo-gutenberg-products-block'
						) }
						checked={ transparentButton }
						onChange={ () =>
							setAttributes( {
								transparentButton: ! transparentButton,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<button
				className={ classnames(
					'wc-block-mini-cart__button',
					colorClassNames
				) }
				style={ colorStyle }
			>
				<span className="wc-block-mini-cart__amount">
					{ formatPrice( productTotal ) }
				</span>
				<QuantityBadge
					count={ productCount }
					colorClassNames={ colorClassNames }
					style={ colorStyle }
				/>
			</button>
			<CartCheckoutCompatibilityNotice blockName="mini-cart" />
		</div>
	);
};

export default MiniCartBlock;
