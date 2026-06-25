/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import { Icon, commentContent } from '@wordpress/icons';
import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	Placeholder,
	ToggleControl,
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
import type { ReviewsByCategoryEditorProps } from './types';

/**
 * Component to handle edit mode of "Reviews by Category".
 *
 * @param {Object}            props                Incoming props for the component.
 * @param {Object}            props.attributes     Incoming block attributes.
 * @param {function(any):any} props.debouncedSpeak
 * @param {function(any):any} props.setAttributes  Setter for block attributes.
 */
const ReviewsByCategoryEditor = ( {
	attributes,
	debouncedSpeak,
	setAttributes,
}: ReviewsByCategoryEditorProps ) => {
	const { editMode, categoryIds } = attributes;

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<ToolsPanel
					label={ __( 'Category', 'woocommerce' ) }
					resetAll={ () => setAttributes( { categoryIds: [] } ) }
				>
					<ToolsPanelItem
						hasValue={ () =>
							( attributes.categoryIds || [] ).length > 0
						}
						label={ __( 'Category', 'woocommerce' ) }
						onDeselect={ () =>
							setAttributes( { categoryIds: [] } )
						}
						isShownByDefault
					>
						<ProductCategoryControl
							selected={ attributes.categoryIds }
							onChange={ ( value = [] ) => {
								const ids = value.map( ( { id } ) => id );
								setAttributes( { categoryIds: ids } );
							} }
							isCompact={ true }
							showReviewCount={ true }
						/>
					</ToolsPanelItem>
				</ToolsPanel>
				<ToolsPanel
					label={ __( 'Content', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							showProductName: true,
							showReviewRating: true,
							showReviewerName: true,
							showReviewImage: true,
							showReviewDate: true,
							showReviewContent: true,
							imageType: 'reviewer',
						} )
					}
				>
					<ToolsPanelItem
						hasValue={ () => ! attributes.showProductName }
						label={ __( 'Product name', 'woocommerce' ) }
						onDeselect={ () =>
							setAttributes( { showProductName: true } )
						}
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Product name', 'woocommerce' ) }
							checked={ attributes.showProductName }
							onChange={ () =>
								setAttributes( {
									showProductName:
										! attributes.showProductName,
								} )
							}
						/>
					</ToolsPanelItem>
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
				__(
					'Now displaying a preview of the reviews for the products in the selected categories.',
					'woocommerce'
				)
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
				label={ __( 'Reviews by Category', 'woocommerce' ) }
				className="wc-block-reviews-by-category"
			>
				{ __(
					'Show product reviews from specific categories.',
					'woocommerce'
				) }
				<div className="wc-block-reviews__selection">
					<ProductCategoryControl
						selected={ attributes.categoryIds }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { categoryIds: ids } );
						} }
						showReviewCount={ true }
					/>
					<Button variant="primary" onClick={ onDone }>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	if ( ! categoryIds || editMode ) {
		return renderEditMode();
	}

	const buttonTitle = __( 'Edit selected categories', 'woocommerce' );

	return (
		<>
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
				name={ __( 'Reviews by Category', 'woocommerce' ) }
				noReviewsPlaceholder={ NoReviewsPlaceholder }
			/>
		</>
	);
};

export default withSpokenMessages( ReviewsByCategoryEditor );
