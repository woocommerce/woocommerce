/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';

export default class Section extends Component {
	constructor( props ) {
		super( props );
		const { title } = props;

		this.state = {
			titleInput: title,
		};

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

	render() {
		const { component: SectionComponent, ...props } = this.props;
		const { titleInput } = this.state;

		return (
			<div className="woocommerce-dashboard-section">
				<SectionComponent
					onTitleChange={ this.onTitleChange }
					onTitleBlur={ this.onTitleBlur }
					titleInput={ titleInput }
					{ ...props }
				/>
			</div>
		);
	}
}
