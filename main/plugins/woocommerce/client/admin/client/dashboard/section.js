/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { xor } from 'lodash';

/**
 * Internal dependencies
 */
import SectionControls from './section-controls';

export default class Section extends Component {
	constructor( props ) {
		super( props );
		const { title } = props;

		this.state = {
			titleInput: title,
		};

		this.onToggleHiddenBlock = this.onToggleHiddenBlock.bind( this );
		this.onTitleChange = this.onTitleChange.bind( this );
		this.onTitleBlur = this.onTitleBlur.bind( this );
	}

	onTitleChange( updatedTitle ) {
		this.setState( { titleInput: updatedTitle } );
	}

	onTitleBlur() {
		const { onTitleUpdate, title } = this.props;
		const { titleInput } = this.state;

		if ( titleInput === '' ) {
			this.setState( { titleInput: title } );
		} else if ( onTitleUpdate ) {
			onTitleUpdate( titleInput );
		}
	}

	onToggleHiddenBlock( key ) {
		return () => {
			const hiddenBlocks = xor( this.props.hiddenBlocks, [ key ] );
			this.props.onChangeHiddenBlocks( hiddenBlocks );
		};
	}

	render() {
		const { component: SectionComponent, ...props } = this.props;
		const { titleInput } = this.state;

		return (
			<div className="woocommerce-dashboard-section">
				<SectionComponent
					onTitleChange={ this.onTitleChange }
					onTitleBlur={ this.onTitleBlur }
					onToggleHiddenBlock={ this.onToggleHiddenBlock }
					titleInput={ titleInput }
					controls={ SectionControls }
					{ ...props }
				/>
			</div>
		);
	}
}
