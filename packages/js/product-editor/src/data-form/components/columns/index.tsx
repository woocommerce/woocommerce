/**
 * External dependencies
 */
import { Template } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductColumn } from '../column';

type ProductColumnsProps = {
	columnsTemplate: Template;
	postType: string;
	productId: number;
};

export function ProductColumns( {
	columnsTemplate,
	postType,
	productId,
}: ProductColumnsProps ) {
	const columns = columnsTemplate[ 2 ] || [];

	// Basic container for columns, styling might be needed later
	return (
		<div style={ { display: 'flex', gap: '16px' } }>
			{ columns.map( ( columnTemplate, index ) => {
				if ( columnTemplate[ 0 ] === 'core/column' ) {
					return (
						<ProductColumn
							key={
								columnTemplate[ 1 ]?._templateBlockId || index
							}
							columnTemplate={ columnTemplate }
							postType={ postType }
							productId={ productId }
						/>
					);
				}
				return null;
			} ) }
		</div>
	);
}
