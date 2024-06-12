/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import sanitizeHTML from '../../lib/sanitize-html';

export class CheckSubscriptionStickyFooter extends Component {
	constructor( props ) {
		super( props );
	}

	render() {
		const classes = classnames(
			'woocommerce-check-subscription-sticky-footer',
			'woocommerce-check-subscription-sticky-footer__' + this.props.colorScheme
		);

		return (
			<div className={ classes }>
				<Notice
					status="info"
				>
					<div
						dangerouslySetInnerHTML={ sanitizeHTML(
							sprintf(
								/* translators: 1. URL to renew product subscription 2. product name */
								__( '<strong><a href="%s">Renew %s</a></strong> to continue receiving updates and streamlined support', 'woocommerce' ),
								'#',
								this.props.productName
							)
						) }
					></div>
				</Notice>
			</div>
		)
	}
}
