/**
 * External dependencies
 */
import { isItemValid } from '@wordpress/dataviews';
import type { Form } from '@wordpress/dataviews';
import {
	createElement,
	useState,
	useMemo,
	useEffect,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useLayoutTemplate } from '@woocommerce/block-templates';
import classNames from 'classnames';
import {
	// @ts-expect-error missing types.
	__experimentalHeading as Heading,
	// @ts-expect-error missing types.
	__experimentalText as Text,
	// @ts-expect-error missing types.
	__experimentalHStack as HStack,
	// @ts-expect-error missing types.
	__experimentalVStack as VStack,
	FlexItem,
	Button,
} from '@wordpress/components';
// @ts-expect-error missing types.
// eslint-disable-next-line @woocommerce/dependency-group
import { privateApis as editorPrivateApis } from '@wordpress/editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { EntityProvider } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import { initBlocks } from '../components/editor/init-blocks';
import { useProductTemplate } from '../hooks/use-product-template';
import { productApiFetchMiddleware } from '../utils/product-apifetch-middleware';
import { ProductTemplate } from '../types';
import { ProductTabs } from './product-tabs';

const { NavigableRegion } = unlock( editorPrivateApis );

productApiFetchMiddleware( () => true );

function getLayoutTemplateId(
	productTemplate: ProductTemplate | undefined | null,
	postType: string
) {
	if ( productTemplate?.layoutTemplateId ) {
		return productTemplate.layoutTemplateId;
	}

	if ( postType === 'product_variation' ) {
		return 'product-variation';
	}

	// Fallback to simple product if no layout template is set.
	return 'simple-product';
}

const form: Form = {
	type: 'regular',
	fields: [ 'name', 'status' ],
};

type ProductEditProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	actions?: React.JSX.Element;
	postType: string;
	postId: string;
};

export default function ProductEditWithOldForm( {
	subTitle,
	actions,
	className,
	hideTitleFromUI = true,
	postType,
	postId = '',
}: ProductEditProps ) {
	useEffect( () => {
		initBlocks();
	}, [] );
	const classes = classNames( 'edit-product-page', className, {
		'is-empty': ! postId,
	} );
	const ids = useMemo( () => postId.split( ',' ), [ postId ] );
	const { initialEdits, product } = useSelect(
		( select ) => {
			return {
				initialEdits:
					ids.length === 1
						? select( 'wc/admin/products' ).getProduct( ids[ 0 ] )
						: null,
				product:
					ids.length === 1
						? select( 'core' ).getEntityRecord(
								'postType',
								postType,
								parseInt( ids[ 0 ], 10 )
						  )
						: null,
			};
		},
		[ postType, ids ]
	);
	const { itemWithEdits } = useSelect(
		( select ) => {
			const pr: Product | null =
				ids.length === 1
					? select( 'wc/admin/products' ).getProduct( ids[ 0 ] )
					: null;
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			const { getEntityRecordNonTransientEdits } = select( 'core' );

			const _edits: Product | null =
				pr &&
				getEntityRecordNonTransientEdits(
					'postType',
					pr.type,
					ids[ 0 ]
				);

			if ( ! pr ) {
				return { itemWithEdits: null };
			}

			return {
				itemWithEdits: _edits,
			};
		},
		[ ids, postType ]
	);

	const productTemplateId = useMemo(
		() =>
			initialEdits?.meta_data?.find(
				( metaEntry: { key: string } ) =>
					metaEntry.key === '_product_template_id'
			)?.value,
		[ initialEdits?.meta_data ]
	);

	const { productTemplate } = useProductTemplate(
		productTemplateId,
		initialEdits || null
	);
	const { layoutTemplate } = useLayoutTemplate(
		initialEdits ? getLayoutTemplateId( productTemplate, postType ) : null
	);

	const [ , setEdits ] = useState( {} );

	const isUpdateDisabled =
		itemWithEdits && isItemValid( itemWithEdits, [], form );

	const onSubmit = async ( event: Event ) => {
		event.preventDefault();

		// Empty save.

		setEdits( {} );
	};

	return (
		<NavigableRegion
			className={ classes }
			ariaLabel={ __( 'Product Edit', 'woocommerce' ) }
		>
			<div className="edit-product-content">
				{ ! hideTitleFromUI && (
					<VStack
						className="edit-site-page-header"
						as="header"
						spacing={ 0 }
					>
						<HStack className="edit-site-page-header__page-title">
							<Heading
								as="h2"
								level={ 3 }
								weight={ 500 }
								className="edit-site-page-header__title"
								truncate
							>
								{ __( 'Product Edit', 'woocommerce' ) }
							</Heading>
							<FlexItem className="edit-site-page-header__actions">
								{ actions }
							</FlexItem>
						</HStack>
						{ subTitle && (
							<Text
								variant="muted"
								as="p"
								className="edit-site-page-header__sub-title"
							>
								{ subTitle }
							</Text>
						) }
					</VStack>
				) }
				{ ! postId && (
					<p>{ __( 'Select a product to edit', 'woocommerce' ) }</p>
				) }
				{ postId && product && itemWithEdits && (
					<VStack spacing={ 4 } as="form" onSubmit={ onSubmit }>
						<EntityProvider
							kind="postType"
							type={ postType }
							id={ parseInt( postId, 10 ) }
						>
							{ layoutTemplate?.blockTemplates && (
								<ProductTabs
									sectionTemplate={
										layoutTemplate?.blockTemplates
									}
									postType={ postType }
									data={ itemWithEdits }
									onChange={ setEdits }
								/>
							) }

							<FlexItem>
								<Button
									variant="primary"
									type="submit"
									// @ts-expect-error missing type.
									accessibleWhenDisabled
									disabled={ !! isUpdateDisabled }
									__next40pxDefaultSize
								>
									{ __( 'Update', 'woocommerce' ) }
								</Button>
							</FlexItem>
						</EntityProvider>
					</VStack>
				) }
			</div>
		</NavigableRegion>
	);
}
