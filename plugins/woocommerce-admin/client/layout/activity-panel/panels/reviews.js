/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ActivityHeader from '../activity-header';

class ReviewsPanel extends Component {
	render() {
		return <ActivityHeader title={ __( 'Reviews', 'wc-admin' ) } />;
	}
}

export default ReviewsPanel;
