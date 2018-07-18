AnimationSlider
============

This component creates slideable content controlled by an animate prop to direct the contents to slide left or right

## How to use:

```jsx
import AnimationSlider from 'components/animation-slider';

class MySlider extends Component {
	constructor() {
		super();
		this.state = {
			pages: [ 44, 55, 66, 77, 88 ],
			page: 0,
			animate: null,
		};
		this.forward = this.forward.bind( this );
		this.back = this.forward.back( this );
	}

	forward() {
		this.setState( state => ( {
			page: state.page + 1,
			animate: 'left',
		} ) );
	}

	back() {
		this.setState( state => ( {
			page: state.page - 1,
			animate: 'right',
		} ) );
	}

	render() {
		const { page, pages, animate } = this.state;
		return (
			<div>
				<button onClick={ this.back } disabled={ page === 0 }>
					Back
				</button>
				<button onClick={ this.forward } disabled={ page === pages.length + 1 }>
					Forward
				</button>
				<AnimationSlider animationKey={ page } animate={ animate }>
					{ status => (
						<img
							className={ `my-slider my-slider-${ status }` }
							src={ `/pages/${ pages[ page ] }` }
							alt={ pages[ page ] }
						/>
					) }
				</AnimationSlider>
			</div>
		);
	}
}
```

## `AnimationSlider` Props

* `children` (required): A function returning rendered content with argument status, reflecting `CSSTransition` status
* `animationKey` (required): A unique identifier for each slideable page
* `animate`: null, 'left', 'right', to designate which direction to slide on a change
* `focusOnChange`: When set to true, the first focusable element will be focused after an animation has finished

All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition
