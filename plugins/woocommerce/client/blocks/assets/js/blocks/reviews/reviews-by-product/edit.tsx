/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { SearchListItem } from '@woocommerce/editor-components/search-list-control';
import ProductControl from '@woocommerce/editor-components/product-control';
import { commentContent, Icon } from '@wordpress/icons';
import { decodeEntities } from '@wordpress/html-entities';
import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	Placeholder,
	withSpokenMessages,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import EditorContainerBlock from '../editor-container-block';
import NoReviewsPlaceholder from './no-reviews-placeholder';
import {
	getBlockControls,
	getSharedReviewContentControls,
	getSharedReviewListControls,
} from '../edit-utils.js';
import { ReviewsByProductEditorProps } from './types';

const ReviewsByProductEditor = ( {
	attributes,
	debouncedSpeak,
	setAttributes,
}: ReviewsByProductEditorProps ) => {
	const { editMode, productId } = attributes;

	const blockProps = useBlockProps();

	const renderProductControlItem = ( args ) => {
		const { item = 0 } = args;

		return (
			<SearchListItem
				{ ...args }
				item={ {
					...item,
					count: item.details.review_count,
				} }
				countLabel={ sprintf(
					/* translators: %d is the review count. */
					_n(
						'%d review',
						'%d reviews',
						item.details.review_count,
						'woocommerce'
					),
					item.details.review_count
				) }
				aria-label={ sprintf(
					/* translators: %1$s is the item name, and %2$d is the number of reviews for the item. */
					_n(
						'%1$s, has %2$d review',
						'%1$s, has %2$d reviews',
						item.details.review_count,
						'woocommerce'
					),
					decodeEntities( item.name ),
					item.details.review_count
				) }
			/>
		);
	};

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<ToolsPanel
					label={ __( 'Product', 'woocommerce' ) }
					resetAll={ () => setAttributes( { productId: 0 } ) }
				>
					<ToolsPanelItem
						hasValue={ () => !! attributes.productId }
						label={ __( 'Product', 'woocommerce' ) }
						onDeselect={ () => setAttributes( { productId: 0 } ) }
						isShownByDefault
					>
						<ProductControl
							selected={
								attributes.productId
									? [ attributes.productId ]
									: []
							}
							onChange={ ( value = [] ) => {
								const id = value[ 0 ] ? value[ 0 ].id : 0;
								setAttributes( { productId: id } );
							} }
							renderItem={ renderProductControlItem }
							isCompact={ true }
						/>
					</ToolsPanelItem>
				</ToolsPanel>
				<ToolsPanel
					label={ __( 'Content', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							showReviewRating: true,
							showReviewerName: true,
							showReviewImage: true,
							showReviewDate: true,
							showReviewContent: true,
							imageType: 'reviewer',
						} )
					}
				>
					{ getSharedReviewContentControls(
						attributes,
						setAttributes
					) }
				</ToolsPanel>
				<ToolsPanel
					label={ __( 'List Settings', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							showOrderby: true,
							orderby: 'most-recent',
							reviewsOnPageLoad: 10,
							showLoadMore: true,
							reviewsOnLoadMore: 10,
						} )
					}
				>
					{ getSharedReviewListControls( attributes, setAttributes ) }
				</ToolsPanel>
			</InspectorControls>
		);
	};

	const renderEditMode = () => {
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__( 'Showing Reviews by Product block preview.', 'woocommerce' )
			);
		};

		return (
			<Placeholder
				icon={
					<Icon
						icon={ commentContent }
						className="block-editor-block-icon"
					/>
				}
				label={ __( 'Reviews by Product', 'woocommerce' ) }
				className="wc-block-reviews-by-product"
			>
				{ __(
					'Show reviews of your product to build trust',
					'woocommerce'
				) }
				<div className="wc-block-reviews__selection">
					<ProductControl
						selected={
							attributes.productId ? [ attributes.productId ] : []
						}
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
						} }
						queryArgs={ {
							orderby: 'comment_count',
							order: 'desc',
						} }
						renderItem={ renderProductControlItem }
					/>
					<Button variant="primary" onClick={ onDone }>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	if ( ! productId || editMode ) {
		return renderEditMode();
	}

	const buttonTitle = __( 'Edit selected product', 'woocommerce' );

	return (
		<div { ...blockProps }>
			{ getBlockControls( editMode, setAttributes, buttonTitle ) }
			{ getInspectorControls() }
			<EditorContainerBlock
				attributes={ attributes }
				icon={
					<Icon
						icon={ commentContent }
						className="block-editor-block-icon"
					/>
				}
				name={ __( 'Reviews by Product', 'woocommerce' ) }
				noReviewsPlaceholder={ NoReviewsPlaceholder }
			/>
		</div>
	);
};

export default withSpokenMessages( ReviewsByProductEditor );
