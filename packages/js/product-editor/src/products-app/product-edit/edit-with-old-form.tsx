/**
 * External dependencies
 */
import { DataForm, isItemValid } from '@wordpress/dataviews';
import type { Field, Form } from '@wordpress/dataviews';
import {
	createElement,
	useState,
	useMemo,
	useEffect,
	Fragment,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { BlockEditProps, Template, getBlockType } from '@wordpress/blocks';
import { Product } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useLayoutTemplate } from '@woocommerce/block-templates';
import { __experimentalErrorBoundary as ErrorBoundary } from '@woocommerce/components';
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
import { unlock } from '../../lock-unlock';
import { initBlocks } from '../../components/editor/init-blocks';
import { useProductTemplate } from '../../hooks/use-product-template';
import { productApiFetchMiddleware } from '../../utils/product-apifetch-middleware';
import { ProductTemplate } from '../../types';

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
	const { initialEdits } = useSelect(
		( select ) => {
			return {
				initialEdits:
					ids.length === 1
						? select( 'wc/admin/products' ).getProduct( ids[ 0 ] )
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
	const flattendedLayoutTemplate: Template[] = useMemo( () => {
		if ( ! layoutTemplate ) {
			return [];
		}
		return layoutTemplate.blockTemplates.flatMap( ( item: Template ) => {
			if ( item[ 0 ] === 'woocommerce/product-tab' && item[ 2 ] ) {
				return item[ 2 ].flatMap( ( tabItems ) => {
					if ( tabItems[ 0 ] === 'woocommerce/product-section' ) {
						return tabItems[ 2 ] || [];
					}
					return [];
				} );
			}
			return [];
		} );
	}, [ layoutTemplate ] );
	const productRenderedFields: Field< Product >[] = useMemo( () => {
		function isField(
			item: Field< Product > | null
		): item is Field< Product > {
			return !! item;
		}
		const mappedItems: Array< Field< Product > | null > =
			flattendedLayoutTemplate.map( ( block ) => {
				const Block = getBlockType( block[ 0 ] );
				if (
					! Block ||
					Block.name.includes( 'column' ) ||
					Block.name.includes( 'section-description' ) ||
					! block[ 1 ] ||
					! Block.edit
				) {
					return null;
				}
				let id = block[ 1 ]._templateBlockId;
				if ( block[ 1 ].property ) {
					id = block[ 1 ].property;
				}
				return {
					id,
					label: block[ 1 ].label || Block.title,
					render: ( { item: product }: { item: Product } ) => {
						if ( block[ 1 ] && block[ 1 ].property ) {
							return (
								<>
									{ JSON.stringify(
										product[ block[ 1 ].property ]
									) }
								</>
							);
						}
						return null;
					},
					Edit: () => {
						const BlockEdit = Block.edit as
							| React.ComponentType<
									Partial<
										// eslint-disable-next-line @typescript-eslint/no-explicit-any
										BlockEditProps< Record< string, any > >
									> & {
										context?: string;
									}
							  >
							| undefined;
						return (
							<ErrorBoundary
								errorMessage={ sprintf(
									/* translators: %1$s: rating, %2$s: total number of stars */
									__(
										'The %s failed to render.',
										'woocommerce'
									),
									Block.name
								) }
							>
								{ BlockEdit && (
									<BlockEdit
										attributes={ block[ 1 ] || {} }
										context={ postType }
									/>
								) }
							</ErrorBoundary>
						);
					},
				};
			} );
		return mappedItems.filter( isField );
	}, [ flattendedLayoutTemplate ] );

	const updatedForm = useMemo( () => {
		return {
			...form,
			fields: flattendedLayoutTemplate.map( ( field ) => {
				if ( field[ 1 ] && field[ 1 ].property ) {
					return field[ 1 ].property;
				}
				return field[ 1 ] ? field[ 1 ]._templateBlockId : null;
			} ),
		};
	}, [ flattendedLayoutTemplate ] );

	const [ , setEdits ] = useState( {} );

	const isUpdateDisabled =
		itemWithEdits &&
		isItemValid( itemWithEdits, productRenderedFields, form );

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
				{ postId && itemWithEdits && (
					<VStack spacing={ 4 } as="form" onSubmit={ onSubmit }>
						<EntityProvider
							kind="postType"
							type={ postType }
							id={ parseInt( postId, 10 ) }
						>
							<DataForm
								data={ itemWithEdits }
								fields={ productRenderedFields }
								form={ updatedForm }
								onChange={ setEdits }
							/>
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
