/**
 * External dependencies
 */
import { type BlockEditProps } from '@wordpress/blocks';
import {
	useBlockProps,
	__experimentalUseColorProps as useColorProps,
	getTypographyClassesAndStyles as useTypographyProps,
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	useSettings,
} from '@wordpress/block-editor';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import clsx from 'clsx';

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
