/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	InnerBlocks,
	useBlockProps,
	/* eslint-disable */
	/* @ts-ignore module is exported as experimental */
	__experimentalGetBorderClassesAndStyles as getBorderClassesAndStyles,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetColorClassesAndStyles as getColorClassesAndStyles,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetShadowClassesAndStyles as getShadowClassesAndStyles,
	/* eslint-enable */
} from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const blockProps = useBlockProps.save();
	const borderProps = getBorderClassesAndStyles( attributes );
	const colorProps = getColorClassesAndStyles( attributes );
	const spacingProps = getSpacingClassesAndStyles( attributes );
	const shadowProps = getShadowClassesAndStyles( attributes );

	return (
		<div
			{ ...blockProps }
			className={ clsx(
				blockProps.className,
				colorProps.className,
				borderProps.className,
				{
					[ `has-custom-font-size` ]: blockProps?.style?.fontSize,
				}
			) }
			style={ {
				...borderProps.style,
				...colorProps.style,
				...shadowProps.style,
			} }
		>
			<div
				className="accordion-content__wrapper"
				style={ {
					...spacingProps.style,
				} }
			>
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
