// @ts-expect-error: `ServerSideRender ` currently does not have a type definition in WordPress core
// eslint-disable-next-line @woocommerce/dependency-group
import ServerSideRender from '@wordpress/server-side-render';
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	Disabled,
	PanelBody,
	Placeholder,
	ToolbarGroup,
	withSpokenMessages,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import GridLayoutControl from '@woocommerce/editor-components/grid-layout-control';
import ProductTagControl from '@woocommerce/editor-components/product-tag-control';
import ProductOrderbyControl from '@woocommerce/editor-components/product-orderby-control';
import ProductStockControl from '@woocommerce/editor-components/product-stock-control';
import { Icon, tag } from '@wordpress/icons';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';
import { isNumber } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsByTagBlockProps } from './types';
/**
 * Component to handle edit mode of "Products by Tag".
 */
const ProductsByTagBlock = ( {
	attributes,
	name,
	setAttributes,
	debouncedSpeak,
}: ProductsByTagBlockProps ) => {
	const [ changedAttributes, setChangedAttributes ] = useState<
		Partial< ProductsByTagBlockProps[ 'attributes' ] >
	>( {} );
	const [ isEditing, setIsEditing ] = useState( false );

	useEffect( () => {
		if ( ! attributes.tags.length ) {
			// We've removed all selected categories, or no categories have been selected yet.
			setIsEditing( true );
		}
	}, [ attributes.tags.length ] );

	const startEditing = () => {
		setIsEditing( true );
		setChangedAttributes( {} );
	};

	const stopEditing = () => {
		setIsEditing( false );
		setChangedAttributes( {} );
	};

	const save = () => {
		setAttributes( changedAttributes );
		stopEditing();
	};

	const getInspectorControls = () => {
		const {
			columns,
			tagOperator,
			contentVisibility,
			orderby,
			rows,
			alignButtons,
			stockStatus,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __(
						'Product Tag',
						'woo-gutenberg-products-block'
					) }
					initialOpen={ ! attributes.tags.length && ! isEditing }
				>
					<ProductTagControl
						selected={ attributes.tags }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { tags: ids } );
						} }
						operator={ tagOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { tagOperator: value } )
						}
						isCompact={ true }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridLayoutControl
						columns={ columns }
						rows={ rows }
						alignButtons={ alignButtons }
						setAttributes={ setAttributes }
						minColumns={ getSettingWithCoercion(
							'minColumns',
							1,
							isNumber
						) }
						maxColumns={ getSettingWithCoercion(
							'maxColumns',
							6,
							isNumber
						) }
						minRows={ getSettingWithCoercion(
							'minRows',
							6,
							isNumber
						) }
						maxRows={ getSettingWithCoercion(
							'maxRows',
							6,
							isNumber
						) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridContentControl
						settings={ contentVisibility }
						onChange={ ( value ) =>
							setAttributes( { contentVisibility: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Order By', 'woo-gutenberg-products-block' ) }
					initialOpen={ false }
				>
					<ProductOrderbyControl
						setAttributes={ setAttributes }
						value={ orderby }
					/>
				</PanelBody>
				<PanelBody
					title={ __(
						'Filter by stock status',
						'woo-gutenberg-products-block'
					) }
					initialOpen={ false }
				>
					<ProductStockControl
						setAttributes={ setAttributes }
						value={ stockStatus }
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	const renderEditMode = () => {
		const currentAttributes = { ...attributes, ...changedAttributes };
		const onDone = () => {
			save();
			debouncedSpeak(
				__(
					'Showing Products by Tag block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};
		const onCancel = () => {
			stopEditing();
			debouncedSpeak(
				__(
					'Showing Products by Tag block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={
					<Icon icon={ tag } className="block-editor-block-icon" />
				}
				label={ __(
					'Products by Tag',
					'woo-gutenberg-products-block'
				) }
				className="wc-block-products-grid wc-block-product-tag"
			>
				{ __(
					'Display a grid of products from your selected tags.',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-product-tag__selection">
					<ProductTagControl
						selected={ currentAttributes.tags }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setChangedAttributes( {
								...changedAttributes,
								tags: ids,
							} );
						} }
						operator={ currentAttributes.tagOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setChangedAttributes( {
								...changedAttributes,
								tagOperator: value,
							} )
						}
					/>
					<Button isPrimary onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
					<Button
						className="wc-block-product-tag__cancel-button"
						isTertiary
						onClick={ onCancel }
					>
						{ __( 'Cancel', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};
	const renderViewMode = () => {
		const selectedTags = attributes.tags.length;

		return (
			<Disabled>
				{ selectedTags ? (
					<ServerSideRender
						block={ name }
						attributes={ attributes }
					/>
				) : (
					<Placeholder
						icon={
							<Icon
								icon={ tag }
								className="block-editor-block-icon"
							/>
						}
						label={ __(
							'Products by Tag',
							'woo-gutenberg-products-block'
						) }
						className="wc-block-products-grid wc-block-product-tag"
					>
						{ __(
							'This block displays products from selected tags. Select at least one tag to display its products.',
							'woo-gutenberg-products-block'
						) }
					</Placeholder>
				) }
			</Disabled>
		);
	};

	if ( attributes.isPreview ) {
		return gridBlockPreview;
	}

	return getSetting( 'hasTags', true ) ? (
		<>
			<BlockControls>
				<ToolbarGroup
					controls={ [
						{
							icon: 'edit',
							title: __(
								'Edit selected tags',
								'woo-gutenberg-products-block'
							),
							onClick: () =>
								isEditing ? stopEditing() : startEditing(),
							isActive: isEditing,
						},
					] }
				/>
			</BlockControls>
			{ getInspectorControls() }
			{ isEditing ? renderEditMode() : renderViewMode() }
		</>
	) : (
		<Placeholder
			icon={ <Icon icon={ tag } className="block-editor-block-icon" /> }
			label={ __( 'Products by Tag', 'woo-gutenberg-products-block' ) }
			className="wc-block-products-grid wc-block-product-tag"
		>
			{ __(
				'This block displays products from selected tags. To use it you first need to create products and assign tags to them.',
				'woo-gutenberg-products-block'
			) }
		</Placeholder>
	);
};
export default withSpokenMessages( ProductsByTagBlock );
