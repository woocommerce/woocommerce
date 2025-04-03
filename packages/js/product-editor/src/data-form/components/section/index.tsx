/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import classNames from 'classnames';
import { Product } from '@woocommerce/data';
import { DataForm } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { SectionHeader } from '../../../components/section-header';
import { useDataFormProductFields } from '../use-data-form-product-fields';
import { ProductColumns } from '../columns';

type ProductSectionProps = {
	sectionTemplate: Template;
	postType: string;
	productId: number;
};

export function ProductSection( {
	sectionTemplate,
	productId,
	postType,
}: ProductSectionProps ) {
	const { description, title, blockGap } = sectionTemplate[ 1 ] as {
		description: string;
		title: string;
		blockGap: string;
	};

	const fieldGroups = useDataFormProductFields( sectionTemplate[ 2 ] );
	const { editEntityRecord } = useDispatch( coreDataStore );
	const { record, hasFinishedResolution } = useSelect(
		( select ) => {
			const {
				getEditedEntityRecord,
				hasFinishedResolution: hasFinished,
			} = select( coreDataStore );

			const args = [ 'postType', postType, productId ];
			return {
				// @ts-expect-error Type definitions are missing
				record: getEditedEntityRecord( ...args ) as Product,
				// @ts-expect-error Type definitions are missing
				hasFinishedResolution: hasFinished(
					'getEditedEntityRecord',
					args
				),
			};
		},
		[ postType, productId ]
	);

	const nestedClassNames = classNames(
		'woocommerce-product-editor',
		'wp-block-woocommerce-product-section-header__content',
		`wp-block-woocommerce-product-section-header__content--block-gap-${ blockGap }`
	);
	const SectionTagName = title ? 'fieldset' : 'div';

	const onChange = ( edits: Partial< Product > ) => {
		editEntityRecord( 'postType', postType, productId, edits );
	};

	return (
		<SectionTagName className="woocommerce-product-section">
			{ title && (
				<SectionHeader
					description={ description }
					sectionTagName={ SectionTagName }
					title={ title }
				/>
			) }

			<div className={ nestedClassNames }>
				{ hasFinishedResolution &&
					fieldGroups.map( ( group, index ) => {
						if ( group.type === 'fields' ) {
							const form = {
								type: 'regular' as const,
								fields: group.content.map(
									( field ) => field.id
								),
							};
							return (
								<DataForm
									key={ index }
									fields={ group.content }
									form={ form }
									onChange={ onChange }
									data={ record }
								/>
							);
						}
						return (
							<ProductColumns
								key={ index }
								columnsTemplate={ group.content }
								postType={ postType }
								productId={ productId }
							/>
						);
					} ) }
			</div>
		</SectionTagName>
	);
}
