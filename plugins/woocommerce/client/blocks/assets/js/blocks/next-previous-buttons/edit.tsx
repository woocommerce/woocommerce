/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { isRTL } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	useBlockProps,
	/* eslint-disable */
	/* @ts-ignore module is exported as experimental */
	__experimentalUseBorderProps as useBorderProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalUseColorProps as useColorProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetShadowClassesAndStyles as useShadowProps,
	/* eslint-enable */
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PrevIcon, NextIcon } from './icons';

const getVerticalAlignmentClass = ( attributes: BlockAttributes ) => {
	const verticalAlignment = attributes?.layout?.verticalAlignment;

	if ( verticalAlignment === 'top' ) {
		return 'aligntop';
	}
	if ( verticalAlignment === 'bottom' ) {
		return 'alignbottom';
	}
	// Default to center.
	return '';
};

export const Edit = ( { attributes }: { attributes: BlockAttributes } ) => {
	const verticalAlignmentClass = getVerticalAlignmentClass( attributes );
	const { style, ...blockProps } = useBlockProps( {
		className: clsx(
			'wc-block-next-previous-buttons',
			verticalAlignmentClass
		),
	} );

	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const shadowProps = useShadowProps( attributes );

	const buttonClassName = clsx(
		'wc-block-next-previous-buttons__button',
		borderProps.className,
		colorProps.className,
		spacingProps.className,
		shadowProps.className
	);

	const buttonStyles = {
		...style,
		...borderProps.style,
		...colorProps.style,
		...spacingProps.style,
		...shadowProps.style,
	};

	const rtl = isRTL();
	const LeftComponent = rtl ? NextIcon : PrevIcon;
	const RightComponent = rtl ? PrevIcon : NextIcon;

	return (
		<div { ...blockProps }>
			<button
				className={ buttonClassName }
				style={ buttonStyles }
				disabled
			>
				<LeftComponent className="wc-block-next-previous-buttons__icon wc-block-next-previous-buttons__icon--left" />
			</button>
			<button className={ buttonClassName } style={ buttonStyles }>
				<RightComponent className="wc-block-next-previous-buttons__icon wc-block-next-previous-buttons__icon--right" />
			</button>
		</div>
	);
};
