/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { trash, Icon } from '@wordpress/icons';
import ChevronUpIcon from 'gridicons/dist/chevron-up';
import ChevronDownIcon from 'gridicons/dist/chevron-down';
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
						label={ __( 'Section title', 'woocommerce' ) }
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
								icon={ <ChevronUpIcon /> }
								label={ __( 'Move up', 'woocommerce' ) }
								size={ 20 }
								className="icon-control"
							/>
							{ __( 'Move up', 'woocommerce' ) }
						</MenuItem>
					) }
					{ ! isLast && (
						<MenuItem isClickable onInvoke={ this.onMoveDown }>
							<Icon
								icon={ <ChevronDownIcon /> }
								size={ 20 }
								label={ __( 'Move down', 'woocommerce' ) }
								className="icon-control"
							/>
							{ __( 'Move down', 'woocommerce' ) }
						</MenuItem>
					) }
					<MenuItem isClickable onInvoke={ onRemove }>
						<Icon
							icon={ trash }
							size={ 20 }
							label={ __( 'Remove block', 'woocommerce' ) }
							className="icon-control"
						/>
						{ __( 'Remove section', 'woocommerce' ) }
					</MenuItem>
				</div>
			</Fragment>
		);
	}
}

export default SectionControls;
