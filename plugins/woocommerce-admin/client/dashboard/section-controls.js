/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { trash, Icon } from '@wordpress/icons';
import arrowUp from 'gridicons/dist/chevron-up';
import arrowDown from 'gridicons/dist/chevron-down';
import { Component, Fragment } from '@wordpress/element';
import { MenuItem } from '@woocommerce/components';

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
		const {
			onRemove,
			isFirst,
			isLast,
			onTitleBlur,
			onTitleChange,
			titleInput,
		} = this.props;

		return (
			<Fragment>
				<div className="woocommerce-ellipsis-menu__item">
					<TextControl
						label={ __( 'Section Title', 'woocommerce-admin' ) }
						onBlur={ onTitleBlur }
						onChange={ onTitleChange }
						required
						value={ titleInput }
					/>
				</div>
				<div className="woocommerce-dashboard-section-controls">
					{ ! isFirst && (
						<MenuItem isClickable onInvoke={ this.onMoveUp }>
							<Icon
								icon={ arrowUp }
								label={ __( 'Move up' ) }
								size={ 20 }
								className="icon-control"
							/>
							{ __( 'Move up', 'woocommerce-admin' ) }
						</MenuItem>
					) }
					{ ! isLast && (
						<MenuItem isClickable onInvoke={ this.onMoveDown }>
							<Icon
								icon={ arrowDown }
								size={ 20 }
								label={ __( 'Move Down' ) }
								className="icon-control"
							/>
							{ __( 'Move Down', 'woocommerce-admin' ) }
						</MenuItem>
					) }
					<MenuItem isClickable onInvoke={ onRemove }>
						<Icon
							icon={ trash }
							size={ 20 }
							label={ __( 'Remove block' ) }
							className="icon-control"
						/>
						{ __( 'Remove section', 'woocommerce-admin' ) }
					</MenuItem>
				</div>
			</Fragment>
		);
	}
}

export default SectionControls;
