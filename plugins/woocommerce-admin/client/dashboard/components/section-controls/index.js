/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MenuItem } from '@woocommerce/components';
import './style.scss';

class SectionControls extends Component {
	constructor( props ) {
		super( props );
		this.onMoveUp = this.onMoveUp.bind( this );
		this.onMoveDown = this.onMoveDown.bind( this );
	}

	onMoveUp() {
		const { onMove, onToggle } = this.props;
		onMove( -1 );
		// Close the dropdown
		onToggle();
	}

	onMoveDown() {
		const { onMove, onToggle } = this.props;
		onMove( 1 );
		// Close the dropdown
		onToggle();
	}

	render() {
		const { onRemove, isFirst, isLast } = this.props;

		if ( ! window.wcAdminFeatures[ 'analytics-dashboard/customizable' ] ) {
			return null;
		}

		return (
			<div className="woocommerce-section-controls">
				{ ! isFirst && (
					<MenuItem isClickable onInvoke={ this.onMoveUp }>
						<Icon icon={ 'arrow-up-alt2' } label={ __( 'Move up' ) } />
						{ __( 'Move up', 'woocommerce-admin' ) }
					</MenuItem>
				) }
				{ ! isLast && (
					<MenuItem isClickable onInvoke={ this.onMoveDown }>
						<Icon icon={ 'arrow-down-alt2' } label={ __( 'Move Down' ) } />
						{ __( 'Move Down', 'woocommerce-admin' ) }
					</MenuItem>
				) }
				<MenuItem isClickable onInvoke={ onRemove }>
					<Icon icon={ 'trash' } label={ __( 'Remove block' ) } />
					{ __( 'Remove section', 'woocommerce-admin' ) }
				</MenuItem>
			</div>
		);
	}
}

export default SectionControls;
