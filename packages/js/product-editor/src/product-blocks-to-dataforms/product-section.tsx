/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import { SectionHeader } from '@woocommerce/components';
import classNames from 'classnames';
import { DataForm, DataFormProps, Field, Form } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	TemplateBlockAttributes,
	transpileBlockToDataformField,
} from './transpile-block-to-dataform-fields';

type ProductSectionProps = {
	sectionTemplate: Template;
	postType: string;
} & Omit< DataFormProps< Product >, 'fields' | 'form' >;

export function ProductSection( {
	sectionTemplate,
	postType,
	...dataFormProps
}: ProductSectionProps ) {
	const { description, title, blockGap } = sectionTemplate[ 1 ] as {
		description: string;
		title: string;
		blockGap: string;
	};

	const nestedClassNames = classNames(
		'wp-block-woocommerce-product-section-header__content',
		`wp-block-woocommerce-product-section-header__content--block-gap-${ blockGap }`
	);
	const SectionTagName = title ? 'fieldset' : 'div';

	const fields = useMemo( () => {
		return ( sectionTemplate[ 2 ] || [] )
			.map( ( template ) =>
				transpileBlockToDataformField(
					template[ 0 ],
					template[ 1 ] as TemplateBlockAttributes,
					template[ 2 ],
					{
						postType,
					}
				)
			)
			.filter( ( field ): field is Field< Product > => !! field );
	}, [ sectionTemplate, postType ] );

	const updatedForm: Form = useMemo( () => {
		return {
			type: 'regular',
			fields: ( sectionTemplate[ 2 ] || [] ).map( ( field ) => {
				if ( field[ 1 ] && field[ 1 ].property ) {
					return field[ 1 ].property;
				}
				return field[ 1 ] ? field[ 1 ]._templateBlockId : null;
			} ),
		};
	}, [ sectionTemplate ] );

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
				<DataForm
					{ ...dataFormProps }
					fields={ fields }
					form={ updatedForm }
				/>
			</div>
		</SectionTagName>
	);
}
