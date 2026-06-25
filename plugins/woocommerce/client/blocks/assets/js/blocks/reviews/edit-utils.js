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
	RangeControl,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

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
		</>
	);
};

export const getSharedReviewListControls = ( attributes, setAttributes ) => {
	const minPerPage = 1;
	const maxPerPage = 20;

	return (
		<>
			<ToolsPanelItem
				hasValue={ () => ! attributes.showOrderby }
				label={ __( 'Order by', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { showOrderby: true } ) }
				isShownByDefault
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Order by', 'woocommerce' ) }
					checked={ attributes.showOrderby }
					onChange={ () =>
						setAttributes( {
							showOrderby: ! attributes.showOrderby,
						} )
					}
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => attributes.orderby !== 'most-recent' }
				label={ __( 'Order Product Reviews by', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { orderby: 'most-recent' } ) }
				isShownByDefault
			>
				<SelectControl
					label={ __( 'Order Product Reviews by', 'woocommerce' ) }
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
				hasValue={ () => attributes.reviewsOnPageLoad !== 10 }
				label={ __( 'Starting Number of Reviews', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { reviewsOnPageLoad: 10 } ) }
				isShownByDefault
			>
				<RangeControl
					label={ __( 'Starting Number of Reviews', 'woocommerce' ) }
					value={ attributes.reviewsOnPageLoad }
					onChange={ ( reviewsOnPageLoad ) =>
						setAttributes( { reviewsOnPageLoad } )
					}
					max={ maxPerPage }
					min={ minPerPage }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () =>
					! attributes.showLoadMore ||
					attributes.reviewsOnLoadMore !== 10
				}
				label={ __( 'Load more', 'woocommerce' ) }
				onDeselect={ () =>
					setAttributes( {
						showLoadMore: true,
						reviewsOnLoadMore: 10,
					} )
				}
				isShownByDefault
			>
				<div className="wc-block-reviews__tools-panel-item-container">
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Load more', 'woocommerce' ) }
						checked={ attributes.showLoadMore }
						onChange={ () =>
							setAttributes( {
								showLoadMore: ! attributes.showLoadMore,
							} )
						}
					/>
					{ attributes.showLoadMore && (
						<RangeControl
							label={ __( 'Load More Reviews', 'woocommerce' ) }
							value={ attributes.reviewsOnLoadMore }
							onChange={ ( reviewsOnLoadMore ) =>
								setAttributes( { reviewsOnLoadMore } )
							}
							max={ maxPerPage }
							min={ minPerPage }
						/>
					) }
				</div>
			</ToolsPanelItem>
		</>
	);
};
