/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { BlockControls } from '@wordpress/block-editor';
import { getAdminLink, getSetting } from '@woocommerce/settings';
import {
	Notice,
	ToggleControl,
	ToolbarGroup,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

const MIN_REVIEW_COUNT = 1;
const MAX_REVIEW_COUNT = 20;
const DEFAULT_REVIEW_COUNT = 10;

const normalizeReviewCount = ( value ) => {
	const parsedValue = Number.parseInt( value, 10 );
	const reviewCount = Number.isNaN( parsedValue )
		? DEFAULT_REVIEW_COUNT
		: parsedValue;
	return Math.max(
		MIN_REVIEW_COUNT,
		Math.min( MAX_REVIEW_COUNT, reviewCount )
	);
};
export const getBlockControls = ( editMode, setAttributes, buttonTitle ) => (
	<BlockControls>
		<ToolbarGroup
			controls={ [
				{
					icon: 'edit',
					title: buttonTitle,
					onClick: () => setAttributes( { editMode: ! editMode } ),
					isActive: editMode,
				},
			] }
		/>
	</BlockControls>
);

export const getSharedReviewContentControls = ( attributes, setAttributes ) => {
	const showAvatars = getSetting( 'showAvatars', true );
	const reviewRatingsEnabled = getSetting( 'reviewRatingsEnabled', true );
	return (
		<>
			<ToolsPanelItem
				hasValue={ () => ! attributes.showReviewRating }
				label={ __( 'Product rating', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { showReviewRating: true } ) }
				isShownByDefault
			>
				<div className="wc-block-reviews__tools-panel-item-container">
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Product rating', 'woocommerce' ) }
						checked={ attributes.showReviewRating }
						onChange={ () =>
							setAttributes( {
								showReviewRating: ! attributes.showReviewRating,
							} )
						}
					/>
					{ attributes.showReviewRating && ! reviewRatingsEnabled && (
						<Notice
							className="wc-block-base-control-notice"
							isDismissible={ false }
						>
							{ createInterpolateElement(
								__(
									'Product rating is disabled in your <a>store settings</a>.',
									'woocommerce'
								),
								{
									a: (
										// eslint-disable-next-line jsx-a11y/anchor-has-content
										<a
											href={ getAdminLink(
												'admin.php?page=wc-settings&tab=products'
											) }
											target="_blank"
											rel="noopener noreferrer"
										/>
									),
								}
							) }
						</Notice>
					) }
				</div>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => ! attributes.showReviewerName }
				label={ __( 'Reviewer name', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { showReviewerName: true } ) }
				isShownByDefault
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Reviewer name', 'woocommerce' ) }
					checked={ attributes.showReviewerName }
					onChange={ () =>
						setAttributes( {
							showReviewerName: ! attributes.showReviewerName,
						} )
					}
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => ! attributes.showReviewDate }
				label={ __( 'Review date', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { showReviewDate: true } ) }
				isShownByDefault
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Review date', 'woocommerce' ) }
					checked={ attributes.showReviewDate }
					onChange={ () =>
						setAttributes( {
							showReviewDate: ! attributes.showReviewDate,
						} )
					}
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => ! attributes.showReviewContent }
				label={ __( 'Review content', 'woocommerce' ) }
				onDeselect={ () =>
					setAttributes( { showReviewContent: true } )
				}
				isShownByDefault
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Review content', 'woocommerce' ) }
					checked={ attributes.showReviewContent }
					onChange={ () =>
						setAttributes( {
							showReviewContent: ! attributes.showReviewContent,
						} )
					}
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () =>
					! attributes.showReviewImage ||
					attributes.imageType !== 'reviewer'
				}
				label={ __( 'Image', 'woocommerce' ) }
				onDeselect={ () =>
					setAttributes( {
						showReviewImage: true,
						imageType: 'reviewer',
					} )
				}
				isShownByDefault
			>
				<div className="wc-block-reviews__tools-panel-item-container">
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Image', 'woocommerce' ) }
						checked={ attributes.showReviewImage }
						onChange={ () =>
							setAttributes( {
								showReviewImage: ! attributes.showReviewImage,
							} )
						}
					/>
					{ attributes.showReviewImage && (
						<>
							<ToggleGroupControl
								label={ __( 'Review image', 'woocommerce' ) }
								isBlock
								value={ attributes.imageType }
								onChange={ ( value ) =>
									setAttributes( { imageType: value } )
								}
							>
								<ToggleGroupControlOption
									value="reviewer"
									label={ __(
										'Reviewer photo',
										'woocommerce'
									) }
								/>
								<ToggleGroupControlOption
									value="product"
									label={ __( 'Product', 'woocommerce' ) }
								/>
							</ToggleGroupControl>
							{ attributes.imageType === 'reviewer' &&
								! showAvatars && (
									<Notice
										className="wc-block-base-control-notice"
										isDismissible={ false }
									>
										{ createInterpolateElement(
											__(
												'Reviewer photo is disabled in your <a>site settings</a>.',
												'woocommerce'
											),
											{
												a: (
													// eslint-disable-next-line jsx-a11y/anchor-has-content
													<a
														href={ getAdminLink(
															'options-discussion.php'
														) }
														target="_blank"
														rel="noopener noreferrer"
													/>
												),
											}
										) }
									</Notice>
								) }
						</>
					) }
				</div>
			</ToolsPanelItem>
		</>
	);
};

export const getSharedReviewListControls = (
	attributes,
	setAttributes,
	{ showOffset = false } = {}
) => {
	const defaultOffset = 0;

	return (
		<>
			<ToolsPanelItem
				hasValue={ () => attributes.orderby !== 'most-recent' }
				label={ __( 'Default sort order', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { orderby: 'most-recent' } ) }
				isShownByDefault
			>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Default sort order', 'woocommerce' ) }
					value={ attributes.orderby }
					options={ [
						{
							label: __( 'Most recent', 'woocommerce' ),
							value: 'most-recent',
						},
						{
							label: __( 'Highest rating', 'woocommerce' ),
							value: 'highest-rating',
						},
						{
							label: __( 'Lowest rating', 'woocommerce' ),
							value: 'lowest-rating',
						},
					] }
					onChange={ ( orderby ) => setAttributes( { orderby } ) }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => ! attributes.showOrderby }
				label={ __( 'Show "Order by" dropdown', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { showOrderby: true } ) }
				isShownByDefault
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show "Order by" dropdown', 'woocommerce' ) }
					checked={ attributes.showOrderby }
					onChange={ () =>
						setAttributes( {
							showOrderby: ! attributes.showOrderby,
						} )
					}
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () =>
					attributes.reviewsOnPageLoad !== DEFAULT_REVIEW_COUNT
				}
				label={ __( 'Number of reviews', 'woocommerce' ) }
				onDeselect={ () =>
					setAttributes( {
						reviewsOnPageLoad: DEFAULT_REVIEW_COUNT,
					} )
				}
				isShownByDefault
			>
				<InputControl
					__next40pxDefaultSize
					label={ __( 'Number of reviews', 'woocommerce' ) }
					value={ String( attributes.reviewsOnPageLoad ) }
					onChange={ ( value ) => {
						setAttributes( {
							reviewsOnPageLoad: normalizeReviewCount( value ),
						} );
					} }
					type="number"
					max={ MAX_REVIEW_COUNT }
					min={ MIN_REVIEW_COUNT }
					step={ 1 }
				/>
			</ToolsPanelItem>
			{ showOffset && (
				<ToolsPanelItem
					hasValue={ () =>
						( attributes.offset ?? defaultOffset ) !== defaultOffset
					}
					label={ __( 'Offset', 'woocommerce' ) }
					onDeselect={ () =>
						setAttributes( { offset: defaultOffset } )
					}
					isShownByDefault
				>
					<NumberControl
						__next40pxDefaultSize
						__unstableStateReducer={ ( state ) =>
							Object.is( Number( state.value ), -0 )
								? {
										...state,
										value: String( defaultOffset ),
								  }
								: state
						}
						label={ __( 'Offset', 'woocommerce' ) }
						help={ __(
							'Number of reviews to skip',
							'woocommerce'
						) }
						value={ attributes.offset ?? defaultOffset }
						onChange={ ( value ) => {
							if ( value === '' ) {
								return;
							}

							const offset = Number( value );
							if (
								! Number.isInteger( offset ) ||
								offset < defaultOffset
							) {
								return;
							}

							setAttributes( { offset } );
						} }
						min={ defaultOffset }
						step={ 1 }
					/>
				</ToolsPanelItem>
			) }
			<ToolsPanelItem
				hasValue={ () =>
					! attributes.showLoadMore ||
					attributes.reviewsOnLoadMore !== DEFAULT_REVIEW_COUNT
				}
				label={ __( 'Show "Load more" button', 'woocommerce' ) }
				onDeselect={ () =>
					setAttributes( {
						showLoadMore: true,
						reviewsOnLoadMore: DEFAULT_REVIEW_COUNT,
					} )
				}
				isShownByDefault
			>
				<div className="wc-block-reviews__tools-panel-item-container">
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show "Load more" button', 'woocommerce' ) }
						checked={ attributes.showLoadMore }
						onChange={ () =>
							setAttributes( {
								showLoadMore: ! attributes.showLoadMore,
							} )
						}
					/>
					{ attributes.showLoadMore && (
						<InputControl
							__next40pxDefaultSize
							label={ __( 'Load more reviews', 'woocommerce' ) }
							value={ String( attributes.reviewsOnLoadMore ) }
							onChange={ ( value ) =>
								setAttributes( {
									reviewsOnLoadMore:
										normalizeReviewCount( value ),
								} )
							}
							type="number"
							max={ MAX_REVIEW_COUNT }
							min={ MIN_REVIEW_COUNT }
							step={ 1 }
						/>
					) }
				</div>
			</ToolsPanelItem>
		</>
	);
};
