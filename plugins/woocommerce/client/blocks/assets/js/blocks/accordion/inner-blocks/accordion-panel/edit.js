/**
 * External dependencies
 */
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

export default function Edit( { attributes } ) {
	const { allowedBlocks, templateLock, openByDefault, isSelected } =
		attributes;
	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const shadowProps = useShadowProps( attributes );

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'accordion-content__wrapper',
			style: {
				...spacingProps.style,
			},
		},
		{
			allowedBlocks,
			template: [ [ 'core/paragraph', {} ] ],
			templateLock,
		}
	);

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
			aria-hidden={ ! isSelected && ! openByDefault }
		>
			<div { ...innerBlocksProps } />
		</div>
	);
}
