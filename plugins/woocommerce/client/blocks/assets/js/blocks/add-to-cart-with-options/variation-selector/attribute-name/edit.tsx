/**
 * External dependencies
 */
import { type BlockEditProps } from '@wordpress/blocks';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import clsx from 'clsx';
import {
	useBlockProps,
	/* eslint-disable */
	/* @ts-expect-error no exported member. */
	getTypographyClassesAndStyles as useTypographyProps,
	/* @ts-expect-error no exported member. */
	useSettings,
	/* @ts-ignore module is exported as experimental */
	__experimentalUseColorProps as useColorProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	/* eslint-enable */
} from '@wordpress/block-editor';

interface Attributes {
	className?: string;
}

export default function AttributeNameEdit(
	props: BlockEditProps< Attributes >
) {
	const { attributes } = props;
	const { className } = attributes;

	const colorProps = useColorProps( attributes );

	const [ fluidTypographySettings, layout ] = useSettings(
		'typography.fluid',
		'layout'
	);
	const typographyProps = useTypographyProps( attributes, {
		typography: {
			fluid: fluidTypographySettings,
		},
		layout: {
			wideSize: layout?.wideSize,
		},
	} );

	const spacingProps = useSpacingProps( attributes );

	const blockProps = useBlockProps( {
		className: clsx(
			className,
			colorProps.className,
			typographyProps.className,
			spacingProps.className
		),
		style: {
			...colorProps.stye,
			...typographyProps.style,
			...spacingProps.style,
		},
	} );

	const { data: attribute } =
		useCustomDataContext< ProductResponseAttributeItem >( 'attribute' );

	if ( ! attribute ) return;

	return (
		<label { ...blockProps } htmlFor={ attribute.taxonomy }>
			{ attribute.name }
		</label>
	);
}
