/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ActivityHeader from '../activity-header';
import ProductImage from 'components/product-image';

class ReviewsPanel extends Component {
	render() {
		return (
			<Fragment>
				<ActivityHeader title={ __( 'Reviews', 'wc-admin' ) } />
				<ProductImage product={ null } />
				<ProductImage product={ { images: [] } } />
				<ProductImage
					product={ {
						images: [
							{
								src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
							},
						],
					} }
				/>
			</Fragment>
		);
	}
}

export default ReviewsPanel;
