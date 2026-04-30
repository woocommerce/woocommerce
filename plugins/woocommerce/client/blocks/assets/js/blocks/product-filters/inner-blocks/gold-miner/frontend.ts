/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { DerivedSelectableItem } from '../../../../types/type-defs/selectable-items';

type GoldMinerContext = {
	storeNamespace: string;
	displayLimit: number;
	clawState: 'swinging' | 'extending' | 'retracting' | 'grabbed';
	clawAngle: number;
	clawLength: number;
	targetNuggetIndex: number;
};

type ParentItemContext = {
	item?: DerivedSelectableItem;
};

type GoldMinerStore = {
	state: {
		isExpanded: boolean;
		itemHidden: boolean;
		clawRotation: string;
		clawHeight: string;
		isSwinging: boolean;
	};
	actions: {
		showAll: () => void;
		dropClaw: () => void;
	};
	callbacks: {
		initGame: () => () => void;
	};
};

function getParentItem(
	storeNamespace: string
): DerivedSelectableItem | undefined {
	const parentCtx = getContext< ParentItemContext >( storeNamespace );
	return parentCtx.item;
}

const CLAW_SWING_SPEED = 0.02;
const CLAW_EXTEND_SPEED = 4;
const CLAW_RETRACT_SPEED = 6;
const MAX_CLAW_LENGTH = 280;

const { state }: GoldMinerStore = store< GoldMinerStore >(
	'woocommerce/product-filter-gold-miner',
	{
		state: {
			isExpanded: false,
			get itemHidden(): boolean {
				if ( state.isExpanded ) return false;
				const { storeNamespace, displayLimit } =
					getContext< GoldMinerContext >();
				const item = getParentItem( storeNamespace );
				if ( ! item ) return false;
				return item.index >= displayLimit;
			},
			get clawRotation(): string {
				const ctx = getContext< GoldMinerContext >();
				return `rotate(${ ctx.clawAngle }deg)`;
			},
			get clawHeight(): string {
				const ctx = getContext< GoldMinerContext >();
				return `${ ctx.clawLength }px`;
			},
			get isSwinging(): boolean {
				const ctx = getContext< GoldMinerContext >();
				return ctx.clawState === 'swinging';
			},
		},
		actions: {
			showAll() {
				state.isExpanded = true;
			},
			dropClaw() {
				const ctx = getContext< GoldMinerContext >();
				if ( ctx.clawState !== 'swinging' ) return;
				ctx.clawState = 'extending';
			},
		},
		callbacks: {
			initGame() {
				const ctx = getContext< GoldMinerContext >();
				const { ref } = getElement();
				if ( ! ref ) return () => {};

				let animId = 0;
				let swingDirection = 1;
				let time = 0;

				const groundEl = ref.querySelector(
					'.wc-block-product-filter-gold-miner__ground'
				) as HTMLElement | null;
				const clawArmEl = ref.querySelector(
					'.wc-block-product-filter-gold-miner__claw-arm'
				) as HTMLElement | null;
				const pivotEl = ref.querySelector(
					'.wc-block-product-filter-gold-miner__claw-pivot'
				) as HTMLElement | null;
				const hookEl = ref.querySelector(
					'.wc-block-product-filter-gold-miner__claw-hook'
				) as HTMLElement | null;

				if ( ! groundEl || ! clawArmEl || ! pivotEl || ! hookEl ) {
					return () => {};
				}

				const nuggets = Array.from(
					groundEl.querySelectorAll(
						'.wc-block-product-filter-gold-miner__nugget'
					)
				) as HTMLElement[];

				function getHookPosition(): { x: number; y: number } {
					const pivotRect = pivotEl!.getBoundingClientRect();
					const pivotX =
						pivotRect.left + pivotRect.width / 2;
					const pivotY = pivotRect.top;
					const angleRad = ( ctx.clawAngle * Math.PI ) / 180;
					return {
						x: pivotX + Math.sin( angleRad ) * ctx.clawLength,
						y: pivotY + Math.cos( angleRad ) * ctx.clawLength,
					};
				}

				function checkCollision(): number {
					const hookPos = getHookPosition();
					const hitRadius = 30;

					for ( let i = 0; i < nuggets.length; i++ ) {
						const nugget = nuggets[ i ];
						if ( nugget.classList.contains( 'is-grabbed' ) ) {
							continue;
						}
						const rect = nugget.getBoundingClientRect();
						const nx = rect.left + rect.width / 2;
						const ny = rect.top + rect.height / 2;
						const dist = Math.sqrt(
							( hookPos.x - nx ) ** 2 +
								( hookPos.y - ny ) ** 2
						);
						if ( dist < hitRadius ) {
							return i;
						}
					}
					return -1;
				}

				function toggleItem( nuggetIndex: number ) {
					const nugget = nuggets[ nuggetIndex ];
					if ( ! nugget ) return;

					const input = nugget.querySelector( 'input' );
					if ( input ) {
						input.dispatchEvent(
							new Event( 'change', { bubbles: true } )
						);
					}
				}

				function gameLoop() {
					switch ( ctx.clawState ) {
						case 'swinging':
							time += CLAW_SWING_SPEED;
							ctx.clawAngle = Math.sin( time ) * 75;
							clawArmEl!.style.transform = `rotate(${ ctx.clawAngle }deg)`;
							clawArmEl!.style.height = `${ ctx.clawLength }px`;
							break;

						case 'extending':
							ctx.clawLength += CLAW_EXTEND_SPEED;
							clawArmEl!.style.height = `${ ctx.clawLength }px`;

							const hitIndex = checkCollision();
							if ( hitIndex >= 0 ) {
								ctx.targetNuggetIndex = hitIndex;
								ctx.clawState = 'grabbed';
								nuggets[ hitIndex ].classList.add(
									'is-grabbed'
								);
								hookEl!.classList.add( 'has-nugget' );
							} else if ( ctx.clawLength >= MAX_CLAW_LENGTH ) {
								ctx.clawState = 'retracting';
							}
							break;

						case 'grabbed':
							ctx.clawLength -= CLAW_RETRACT_SPEED;
							clawArmEl!.style.height = `${ ctx.clawLength }px`;

							if ( ctx.clawLength <= 30 ) {
								ctx.clawLength = 30;
								toggleItem( ctx.targetNuggetIndex );
								const nugget =
									nuggets[ ctx.targetNuggetIndex ];
								if ( nugget ) {
									nugget.classList.remove( 'is-grabbed' );
									nugget.classList.toggle( 'is-selected' );
								}
								hookEl!.classList.remove( 'has-nugget' );
								ctx.clawState = 'swinging';
							}
							break;

						case 'retracting':
							ctx.clawLength -= CLAW_RETRACT_SPEED;
							clawArmEl!.style.height = `${ ctx.clawLength }px`;

							if ( ctx.clawLength <= 30 ) {
								ctx.clawLength = 30;
								ctx.clawState = 'swinging';
							}
							break;
					}

					animId = requestAnimationFrame( gameLoop );
				}

				ctx.clawAngle = 0;
				ctx.clawLength = 30;
				ctx.clawState = 'swinging';
				ctx.targetNuggetIndex = -1;

				animId = requestAnimationFrame( gameLoop );

				return () => {
					cancelAnimationFrame( animId );
				};
			},
		},
	},
	{ lock: true }
);

export type { GoldMinerStore };
export { state as goldMinerState };
