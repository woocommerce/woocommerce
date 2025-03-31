/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
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

	const fields = useDataFormProductFields( sectionTemplate[ 2 ] );
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
		'wp-block-woocommerce-product-section-header__content',
		`wp-block-woocommerce-product-section-header__content--block-gap-${ blockGap }`
	);
	const SectionTagName = title ? 'fieldset' : 'div';

	const form = useMemo( () => {
		return {
			type: 'regular' as const,
			fields: sectionTemplate[ 2 ]
				?.filter(
					( field ) => field[ 0 ] === 'woocommerce/product-name-field'
				)
				.map( () => 'name' ),
		};
	}, [ sectionTemplate ] );

	const onChange = ( edits: Partial< Product > ) => {
		editEntityRecord( 'postType', postType, productId, edits );
	};

	return (
		<SectionTagName>
			{ title && (
				<SectionHeader
					description={ description }
					sectionTagName={ SectionTagName }
					title={ title }
				/>
			) }

			<div className={ nestedClassNames }>
				{ hasFinishedResolution && (
					<DataForm
						fields={ fields }
						form={ form }
						onChange={ onChange }
						data={ record }
					/>
				) }
				<p>
					Render DataForm with { sectionTemplate[ 2 ]?.length } fields
				</p>
				<ul>
					{ sectionTemplate[ 2 ]?.map( ( field ) => (
						<li key={ field[ 0 ] }>{ field[ 0 ] }</li>
					) ) }
				</ul>
			</div>
		</SectionTagName>
	);
}
