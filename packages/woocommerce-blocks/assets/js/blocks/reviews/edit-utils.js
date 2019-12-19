/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Fragment, RawHTML } from '@wordpress/element';
import { escapeHTML } from '@wordpress/escape-html';
import {
	Notice,
	ToggleControl,
	Toolbar,
	RangeControl,
	SelectControl,
} from '@wordpress/components';
import { BlockControls } from '@wordpress/editor';
import { getAdminLink } from '@woocommerce/settings';
import {
	ENABLE_REVIEW_RATING,
	SHOW_AVATARS,
} from '@woocommerce/block-settings';
import ToggleButtonControl from '@woocommerce/block-components/toggle-button-control';

export const getBlockControls = ( editMode, setAttributes ) => (
	<BlockControls>
		<Toolbar
			controls={ [
				{
					icon: 'edit',
					title: __( 'Edit', 'woocommerce' ),
					onClick: () => setAttributes( { editMode: ! editMode } ),
					isActive: editMode,
				},
			] }
		/>
	</BlockControls>
);

export const getSharedReviewContentControls = ( attributes, setAttributes ) => {
	return (
		<Fragment>
			<ToggleControl
				label={ __( 'Product rating', 'woocommerce' ) }
				checked={ attributes.showReviewRating }
				onChange={ () =>
					setAttributes( {
						showReviewRating: ! attributes.showReviewRating,
					} )
				}
			/>
			{ attributes.showReviewRating && ! ENABLE_REVIEW_RATING && (
				<Notice
					className="wc-block-reviews__notice"
					isDismissible={ false }
				>
					<RawHTML>
						{ sprintf(
							escapeHTML(
								/* translators: A notice that links to WooCommerce settings. */
								__(
									'Product rating is disabled in your %sstore settings%s.',
									'woocommerce'
								)
							),
							`<a href="${ getAdminLink(
								'admin.php?page=wc-settings&tab=products'
							) }" target="_blank">`,
							'</a>'
						) }
					</RawHTML>
				</Notice>
			) }
			<ToggleControl
				label={ __( 'Reviewer name', 'woocommerce' ) }
				checked={ attributes.showReviewerName }
				onChange={ () =>
					setAttributes( {
						showReviewerName: ! attributes.showReviewerName,
					} )
				}
			/>
			<ToggleControl
				label={ __( 'Image', 'woocommerce' ) }
				checked={ attributes.showReviewImage }
				onChange={ () =>
					setAttributes( {
						showReviewImage: ! attributes.showReviewImage,
					} )
				}
			/>
			<ToggleControl
				label={ __( 'Review date', 'woocommerce' ) }
				checked={ attributes.showReviewDate }
				onChange={ () =>
					setAttributes( {
						showReviewDate: ! attributes.showReviewDate,
					} )
				}
			/>
			<ToggleControl
				label={ __( 'Review content', 'woocommerce' ) }
				checked={ attributes.showReviewContent }
				onChange={ () =>
					setAttributes( {
						showReviewContent: ! attributes.showReviewContent,
					} )
				}
			/>
			{ attributes.showReviewImage && (
				<Fragment>
					<ToggleButtonControl
						label={ __(
							'Review image',
							'woocommerce'
						) }
						value={ attributes.imageType }
						options={ [
							{
								label: __(
									'Reviewer photo',
									'woocommerce'
								),
								value: 'reviewer',
							},
							{
								label: __(
									'Product',
									'woocommerce'
								),
								value: 'product',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { imageType: value } )
						}
					/>
					{ attributes.imageType === 'reviewer' && ! SHOW_AVATARS && (
						<Notice
							className="wc-block-reviews__notice"
							isDismissible={ false }
						>
							<RawHTML>
								{ sprintf(
									escapeHTML(
										/* translators: A notice that links to WordPress settings. */
										__(
											'Reviewer photo is disabled in your %ssite settings%s.',
											'woocommerce'
										)
									),
									`<a href="${ getAdminLink(
										'options-discussion.php'
									) }" target="_blank">`,
									'</a>'
								) }
							</RawHTML>
						</Notice>
					) }
				</Fragment>
			) }
		</Fragment>
	);
};

export const getSharedReviewListControls = ( attributes, setAttributes ) => {
	const minPerPage = 1;
	const maxPerPage = 20;

	return (
		<Fragment>
			<ToggleControl
				label={ __( 'Order by', 'woocommerce' ) }
				checked={ attributes.showOrderby }
				onChange={ () =>
					setAttributes( { showOrderby: ! attributes.showOrderby } )
				}
			/>
			<SelectControl
				label={ __(
					'Order Product Reviews by',
					'woocommerce'
				) }
				value={ attributes.orderby }
				options={ [
					{ label: 'Most recent', value: 'most-recent' },
					{ label: 'Highest Rating', value: 'highest-rating' },
					{ label: 'Lowest Rating', value: 'lowest-rating' },
				] }
				onChange={ ( orderby ) => setAttributes( { orderby } ) }
			/>
			<RangeControl
				label={ __(
					'Starting Number of Reviews',
					'woocommerce'
				) }
				value={ attributes.reviewsOnPageLoad }
				onChange={ ( reviewsOnPageLoad ) =>
					setAttributes( { reviewsOnPageLoad } )
				}
				max={ maxPerPage }
				min={ minPerPage }
			/>
			<ToggleControl
				label={ __( 'Load more', 'woocommerce' ) }
				checked={ attributes.showLoadMore }
				onChange={ () =>
					setAttributes( { showLoadMore: ! attributes.showLoadMore } )
				}
			/>
			{ attributes.showLoadMore && (
				<RangeControl
					label={ __(
						'Load More Reviews',
						'woocommerce'
					) }
					value={ attributes.reviewsOnLoadMore }
					onChange={ ( reviewsOnLoadMore ) =>
						setAttributes( { reviewsOnLoadMore } )
					}
					max={ maxPerPage }
					min={ minPerPage }
				/>
			) }
		</Fragment>
	);
};
