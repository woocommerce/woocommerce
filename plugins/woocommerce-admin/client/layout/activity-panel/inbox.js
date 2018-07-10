/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ActivityHeader from './activity-header';

class InboxPanel extends Component {
	render() {
		return <ActivityHeader title={ __( 'Inbox', 'wc-admin' ) } />;
	}
}

export default InboxPanel;
