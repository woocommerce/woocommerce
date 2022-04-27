/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AnimationSlider from '../';

class BasicExample extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			pages: [ 44, 55, 66, 77, 88 ],
			page: 0,
			animate: null,
		};
		this.forward = this.forward.bind( this );
		this.back = this.back.bind( this );
	}

	forward() {
		this.setState( ( state ) => ( {
			page: state.page + 1,
			animate: 'left',
		} ) );
	}

	back() {
		this.setState( ( state ) => ( {
			page: state.page - 1,
			animate: 'right',
		} ) );
	}

	render() {
		const { page, pages, animate } = this.state;
		const style = {
			margin: '16px 0',
			padding: '8px 16px',
			color: 'white',
			fontWeight: 'bold',
			backgroundColor: '#246EB9',
		};
		return (
			<div>
				<AnimationSlider animationKey={ page } animate={ animate }>
					{ () => <div style={ style }>{ pages[ page ] }</div> }
				</AnimationSlider>
				<button onClick={ this.back } disabled={ page === 0 }>
					Back
				</button>
				<button
					onClick={ this.forward }
					disabled={ page === pages.length - 1 }
				>
					Forward
				</button>
			</div>
		);
	}
}

export const Basic = () => <BasicExample />;

export default {
	title: 'WooCommerce Admin/components/AnimationSlider',
	component: AnimationSlider,
};
