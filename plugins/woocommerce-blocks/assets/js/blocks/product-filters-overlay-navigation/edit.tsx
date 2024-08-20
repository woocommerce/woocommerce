/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps, store as blocksStore } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Icon, close } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type {
	BlockAttributes,
	BlockContext,
	BlockVariationTriggerType,
} from './types';
import { default as productFiltersIcon } from '../product-filters/icon';
import { BlockOverlayAttribute as ProductFiltersBlockOverlayAttribute } from '../product-filters/constants';
import './editor.scss';
import { Inspector } from './inspector-controls';

const OverlayNavigationLabel = ( {
	variation,
}: {
	variation: BlockVariationTriggerType;
} ) => {
	let label = __( 'Close', 'woocommerce' );
	if ( variation === 'open-overlay' ) {
		label = __( 'Filters', 'woocommerce' );
	}

	return <span>{ label }</span>;
};

const OverlayNavigationIcon = ( {
	variation,
	iconSize,
	style,
}: {
	variation: BlockVariationTriggerType;
	iconSize: number | undefined;
	style: BlockAttributes[ 'style' ];
} ) => {
	let icon = close;

	if ( variation === 'open-overlay' ) {
		icon = productFiltersIcon();
	}

	return (
		<Icon
			fill="currentColor"
			icon={ icon }
			style={ {
				width: iconSize || style?.typography?.fontSize || '16px',
				height: iconSize || style?.typography?.fontSize || '16px',
			} }
		/>
	);
};

const OverlayNavigationContent = ( {
	variation,
	iconSize,
	style,
	navigationStyle,
}: {
	variation: BlockVariationTriggerType;
	iconSize: BlockAttributes[ 'iconSize' ];
	style: BlockAttributes[ 'style' ];
	navigationStyle: BlockAttributes[ 'navigationStyle' ];
} ) => {
	const overlayNavigationLabel = (
		<OverlayNavigationLabel variation={ variation } />
	);
	const overlayNavigationIcon = (
		<OverlayNavigationIcon
			variation={ variation }
			iconSize={ iconSize }
			style={ style }
		/>
	);

	if ( navigationStyle === 'label-and-icon' ) {
		if ( variation === 'open-overlay' ) {
			return (
				<>
					{ overlayNavigationIcon }
					{ overlayNavigationLabel }
				</>
			);
		} else if ( variation === 'close-overlay' ) {
			return (
				<>
					{ overlayNavigationLabel }
					{ overlayNavigationIcon }
				</>
			);
		}
	} else if ( navigationStyle === 'label-only' ) {
		return overlayNavigationLabel;
	} else if ( navigationStyle === 'icon-only' ) {
		return overlayNavigationIcon;
	}

	return null;
};

type BlockProps = BlockEditProps< BlockAttributes > & { context: BlockContext };

export const Edit = ( { attributes, setAttributes, context }: BlockProps ) => {
	const { navigationStyle, buttonStyle, iconSize, style, triggerType } =
		attributes;
	const { 'woocommerce/product-filters/overlay': productFiltersOverlayMode } =
		context;
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filters-overlay-navigation', {
			'wp-block-button__link wp-element-button': buttonStyle !== 'link',
		} ),
	} );
	const {
		isWithinProductFiltersOverlayTemplatePart,
	}: {
		isWithinProductFiltersOverlayTemplatePart: boolean;
	} = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		const { getCurrentPostId, getCurrentPostType } =
			select( 'core/editor' );
		const currentPostId = getCurrentPostId< string >();
		const currentPostIdParts = currentPostId?.split( '//' );
		const currentPostType = getCurrentPostType< string >();
		let isProductFiltersOverlayTemplatePart = false;

		if (
			currentPostType === 'wp_template_part' &&
			currentPostIdParts?.length > 1
		) {
			const [ , postId ] = currentPostIdParts;
			isProductFiltersOverlayTemplatePart =
				postId === 'product-filters-overlay';
		}

		return {
			isWithinProductFiltersOverlayTemplatePart:
				isProductFiltersOverlayTemplatePart,
		};
	} );

	const shouldHideBlock = () => {
		if ( triggerType === 'open-overlay' ) {
			if (
				productFiltersOverlayMode ===
				ProductFiltersBlockOverlayAttribute.NEVER
			) {
				return true;
			}

			if ( isWithinProductFiltersOverlayTemplatePart ) {
				return true;
			}
		}

		return false;
	};
	// We need useInnerBlocksProps because Gutenberg only applies layout classes
	// to parent block. We don't have any inner blocks but we want to use the
	// layout controls.
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	const buttonBlockStyles = useSelect(
		( select ) => select( blocksStore ).getBlockStyles( 'core/button' ),
		[]
	);

	const buttonStyles = [
		{ value: 'link', label: __( 'Link', 'woocommerce' ) },
	];

	buttonBlockStyles.forEach(
		( buttonBlockStyle: { name: string; label: string } ) => {
			if ( buttonBlockStyle.name === 'link' ) return;
			buttonStyles.push( {
				value: buttonBlockStyle.name,
				label: buttonBlockStyle.label,
			} );
		}
	);

	if ( shouldHideBlock() ) {
		return (
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				buttonStyles={ buttonStyles }
			/>
		);
	}

	return (
		<nav
			className={ clsx(
				'wc-block-product-filters-overlay-navigation__wrapper',
				`is-style-${ buttonStyle }`,
				{
					'wp-block-button': buttonStyle !== 'link',
				}
			) }
		>
			<div { ...innerBlocksProps }>
				<OverlayNavigationContent
					variation={ triggerType }
					iconSize={ iconSize }
					navigationStyle={ navigationStyle }
					style={ style }
				/>
			</div>
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				buttonStyles={ buttonStyles }
			/>
		</nav>
	);
};
