/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { BlockEditProps, TemplateArray, getBlockType } from '@wordpress/blocks';
import {
	// @ts-expect-error missing types.
	__experimentalHStack as HStack,
	// @ts-expect-error missing types.
	__experimentalVStack as VStack,
} from '@wordpress/components';

type ProductColumnWrapperProps = {
	columns: TemplateArray;
	context?: Record< string, string >;
};

export function ProductColumnWrapper( {
	columns,
	context,
}: ProductColumnWrapperProps ) {
	const columnsWithFields = useMemo( () => {
		return columns.map( ( template ) =>
			( template[ 2 ] || [] ).map( ( child ) => {
				const block = getBlockType( child[ 0 ] );
				if ( ! block || ! child[ 1 ] ) {
					return null;
				}
				return {
					Edit: block.edit as React.ComponentType<
						Partial<
							// eslint-disable-next-line @typescript-eslint/no-explicit-any
							BlockEditProps< Record< string, any > >
						> & {
							context?: Record< string, string >;
						}
					>,
					attributes: child[ 1 ],
				};
			} )
		);
	}, [ columns ] );

	return (
		<HStack>
			{ columnsWithFields.map( ( column, index ) => (
				<VStack key={ index }>
					{ column.map( ( field ) => {
						return (
							field && (
								<field.Edit
									key={ field?.attributes._templateBlockId }
									attributes={ field?.attributes }
									context={ context }
								/>
							)
						);
					} ) }
				</VStack>
			) ) }
		</HStack>
	);
}
