/* global wcOrderMilestoneEgg, Path2D, requestAnimationFrame, cancelAnimationFrame, navigator, history */
( function () {
	'use strict';

	/* eslint-disable no-var */
	var _cfg =
		typeof wcOrderMilestoneEgg !== 'undefined' ? wcOrderMilestoneEgg : {};
	var milestones = _cfg.milestones || {};
	var SVG_DATA = _cfg.svgData || {};
	var ALL_MILESTONES = _cfg.allMilestones || {};
	var LABELS = _cfg.labels || {};
	var DISMISS = _cfg.dismiss || null;
	var shown = {};
	var reducedMotion = !! (
		window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
	);

	/* -- Variant definitions ------------------------------------------------ */
	var VARIANTS = {
		llama: {
			vbW: 789.58,
			vbH: 979.2,
			headTopFrac: 0.32,
			classes: [
				'.cls-1',
				'.cls-3',
				'.cls-4',
				'.cls-5',
				'.cls-6',
				'.cls-7',
				'.cls-8',
			],
			attachX: 415,
			attachY: 275,
		},
		octo: {
			vbW: 910.68,
			vbH: 914.05,
			headTopFrac: 0.3,
			classes: [
				'.cls-1',
				'.cls-2',
				'.cls-3',
				'.cls-4',
				'.cls-5',
				'.cls-6',
			],
			boomOffsetX: 5,
			boomOffsetY: 25,
			stringOffsetX: 0,
		},
		whale: {
			vbW: 1092.3,
			vbH: 736.32,
			headTopFrac: 0.5,
			classes: [
				'.cls-1',
				'.cls-2',
				'.cls-3',
				'.cls-4',
				'.cls-5',
				'.cls-6',
				'.cls-7',
				'.cls-8',
				'.cls-9',
				'.cls-10',
				'.cls-11',
				'.cls-12',
				'.cls-13',
				'.cls-14',
			],
			scale: 2.475,
			boomOffsetX: -5,
			boomOffsetY: 70,
			maxSwing: 0.4,
		},
	};

	var DEFAULTS = {
		headline: 'You got your very first order!',
		body: 'Your store is officially in business, and\nthis is just the beginning.',
		cta: "Let's go!",
		variant: 'llama',
		gravity: 0.0028,
		damp: 0.985,
		hit: 0.0004,
		sway: 1.0,
		cfOn: true,
		cfPile: true,
		cfShape: 'all',
		cfCount: 22,
		cfSize: 1.0,
	};

	/* -- Module-level state (reset per showOverlay call) -------------------- */
	var settings;
	var active, COLOR_LAYERS, HEAD_TOP_LAYER;
	var _contentBB = { topY: 0, centerY: 0, centerX: 0 };
	var ALL_SHAPES = [],
		activeShapes = [];
	var mainAngle = 0,
		mainOmega = 0,
		dropped = false;
	var mx = 0,
		my = 0,
		lastMx = 0,
		mouseVx = 0,
		mActive = false;
	var stickAngle = 25,
		stickAngleVel = 0;
	var _pinataCenterX = 0,
		_pinataCenterY = 0,
		_pinataR = 0,
		_shiftX = 0;
	var COLUMN_W = 2,
		columns = [];
	var live = [],
		settled = [],
		SETTLED_CAP = 1200;
	var bgCV, bgCtx, cfCV, cfCtx, stick;
	var overlayEl,
		svgDefsEl,
		closeOverlay,
		rafId = null;
	var fallingPieces = [],
		protectedEls;
	var eyeEls = [],
		eyeElsSet,
		blinkTimer = null;
	var blinkScaleY = 1;
	var highlightEls = [];
	var eyeGroups = [];
	var currentMilestone = null;
	var pinataEl = null;
	var squishAmp = 0,
		squishVel = 0,
		squishAngle = 0;
	var _previousFocus = null;

	/* -- Layout helpers ----------------------------------------------------- */
	function pinataPxWidth() {
		var w = Math.min( window.innerWidth * 0.21, 266 );
		return Math.max( 154, w ) * ( active.scale || 1.0 ) * 0.9;
	}

	function applyLayout() {
		var w = pinataPxWidth();
		var sc = w / active.vbW;
		var attachX =
			active.attachX !== null && active.attachX !== undefined
				? active.attachX
				: _contentBB.centerX;

		_shiftX = active.shiftX || 0;
		var cont = document.getElementById( 'egg-pinata-container' );
		cont.style.width = w + 'px';
		cont.style.height = ( w * active.vbH ) / active.vbW + 'px';
		cont.style.transform =
			'translateX(' + ( w / 2 - attachX * sc + _shiftX ) + 'px)';

		var topOfContainer =
			window.innerHeight / 2 - 60 - _contentBB.centerY * sc;
		_pinataCenterX = ( _contentBB.centerX - attachX ) * sc + _shiftX;
		_pinataCenterY = window.innerHeight / 2 - 60;
		_pinataR = w * 0.42;

		document.getElementById( 'egg-rope' ).style.height =
			Math.max( 0, topOfContainer ) + 'px';
		var copy = overlayEl.querySelector( '.egg-copy' );
		copy.style.top = Math.round( window.innerHeight * 0.7 ) + 'px';
	}

	function pinataCentre() {
		var sa = Math.sin( mainAngle ),
			ca = Math.cos( mainAngle );
		return {
			x:
				window.innerWidth / 2 +
				_pinataCenterX * ca -
				_pinataCenterY * sa,
			y:
				( dropped ? 0 : -window.innerHeight * 1.2 ) +
				_pinataCenterX * sa +
				_pinataCenterY * ca,
			r: _pinataR,
		};
	}

	/* -- Load pinata -------------------------------------------------------- */
	async function loadPinata( variantKey ) {
		if ( ! VARIANTS[ variantKey ] ) return;

		var svgText = SVG_DATA[ variantKey ];
		if ( ! svgText ) return;

		active = VARIANTS[ variantKey ];
		var v = active;
		var cont = document.getElementById( 'egg-pinata-container' );
		HEAD_TOP_LAYER.els = [];
		HEAD_TOP_LAYER.val = 0;
		HEAD_TOP_LAYER.vel = 0;

		cont.innerHTML = svgText;

		var svg = cont.querySelector( 'svg' );
		pinataEl = svg;
		var firstEl =
			svg &&
			svg.querySelector( 'path,circle,ellipse,polygon,rect,polyline' );
		if ( firstEl ) protectedEls.add( firstEl );
		svg.removeAttribute( 'width' );
		svg.removeAttribute( 'height' );
		svg.setAttribute( 'preserveAspectRatio', 'xMidYMid meet' );

		_contentBB = { topY: 0, centerY: v.vbH / 2, centerX: v.vbW / 2 };
		applyLayout();

		var SPRING_PRESETS = [
			{ stiff: 0.024, damp: 0.84, velMult: 0.3 },
			{ stiff: 0.03, damp: 0.82, velMult: 0.22 },
			{ stiff: 0.027, damp: 0.83, velMult: 0.25 },
			{ stiff: 0.022, damp: 0.85, velMult: 0.32 },
			{ stiff: 0.02, damp: 0.86, velMult: 0.34 },
			{ stiff: 0.034, damp: 0.8, velMult: 0.18 },
			{ stiff: 0.026, damp: 0.83, velMult: 0.28 },
		];
		COLOR_LAYERS = v.classes.map( function ( sel, i ) {
			var p = SPRING_PRESETS[ i % SPRING_PRESETS.length ];
			return {
				sel,
				stiff: p.stiff,
				damp: p.damp,
				velMult: p.velMult,
				els: [].slice.call( svg.querySelectorAll( sel ) ),
				val: 0,
				vel: 0,
			};
		} );

		await new Promise( function ( r ) {
			requestAnimationFrame( r );
		} );
		await new Promise( function ( r ) {
			requestAnimationFrame( r );
		} );

		var minX = Infinity,
			minY = Infinity,
			maxX = -Infinity,
			maxY = -Infinity;
		[].slice
			.call(
				svg.querySelectorAll(
					'path,circle,ellipse,polygon,rect,polyline'
				)
			)
			.forEach( function ( el ) {
				var bb;
				try {
					bb = el.getBBox();
				} catch ( _ ) {
					return;
				}
				if ( bb.width === 0 && bb.height === 0 ) return;
				if ( bb.x < minX ) minX = bb.x;
				if ( bb.y < minY ) minY = bb.y;
				if ( bb.x + bb.width > maxX ) maxX = bb.x + bb.width;
				if ( bb.y + bb.height > maxY ) maxY = bb.y + bb.height;
			} );
		if ( isFinite( minY ) ) {
			_contentBB = {
				topY: minY,
				centerY: ( minY + maxY ) / 2,
				centerX: ( minX + maxX ) / 2,
			};
		}
		applyLayout();

		var headThreshold = _contentBB.topY + ( maxY - minY ) * v.headTopFrac;
		HEAD_TOP_LAYER.els = [];
		COLOR_LAYERS.forEach( function ( layer ) {
			layer.els = layer.els.filter( function ( el ) {
				var bb;
				try {
					bb = el.getBBox();
				} catch ( _ ) {
					return true;
				}
				if ( bb.y + bb.height / 2 < headThreshold ) {
					HEAD_TOP_LAYER.els.push( el );
					return false;
				}
				return true;
			} );
		} );

		// -- Detect eye circles (all groups of concentric circles in head area) --
		eyeEls = [];
		eyeElsSet = null;
		eyeGroups = [];
		var headCircles = [].slice
			.call( svg.querySelectorAll( 'circle' ) )
			.filter( function ( c ) {
				var bb;
				try {
					bb = c.getBBox();
				} catch ( _ ) {
					return false;
				}
				return bb.y + bb.height / 2 < headThreshold;
			} );
		var posMap = {};
		headCircles.forEach( function ( c ) {
			var key =
				Math.round( parseFloat( c.getAttribute( 'cx' ) ) / 15 ) +
				',' +
				Math.round( parseFloat( c.getAttribute( 'cy' ) ) / 15 );
			if ( ! posMap[ key ] ) posMap[ key ] = [];
			posMap[ key ].push( c );
		} );
		Object.keys( posMap ).forEach( function ( k ) {
			var group = posMap[ k ];
			if ( group.length < 2 ) return;
			var sumCx = 0,
				sumCy = 0;
			group.forEach( function ( c ) {
				sumCx += parseFloat( c.getAttribute( 'cx' ) );
				sumCy += parseFloat( c.getAttribute( 'cy' ) );
			} );
			var gCx = sumCx / group.length,
				gCy = sumCy / group.length;
			var pupil = group.reduce( function ( min, el ) {
				return parseFloat( el.getAttribute( 'r' ) || '999' ) <
					parseFloat( min.getAttribute( 'r' ) || '999' )
					? el
					: min;
			}, group[ 0 ] );
			group.forEach( function ( el ) {
				eyeEls.push( el );
				protectedEls.add( el );
			} );
			eyeGroups.push( {
				pupilEl: pupil,
				pupilCx: parseFloat( pupil.getAttribute( 'cx' ) ),
				pupilCy: parseFloat( pupil.getAttribute( 'cy' ) ),
				eyeCx: gCx,
				eyeCy: gCy,
			} );
		} );
		if ( eyeGroups.length ) {
			eyeElsSet = new Set( eyeEls );

			[].slice
				.call( svg.querySelectorAll( 'circle' ) )
				.forEach( function ( c ) {
					if ( eyeElsSet.has( c ) ) return;
					var cx = parseFloat( c.getAttribute( 'cx' ) ),
						cy = parseFloat( c.getAttribute( 'cy' ) );
					var near = eyeGroups.some( function ( g ) {
						return (
							Math.sqrt(
								( cx - g.eyeCx ) * ( cx - g.eyeCx ) +
									( cy - g.eyeCy ) * ( cy - g.eyeCy )
							) < 100
						);
					} );
					if ( ! near ) return;
					protectedEls.add( c );
					eyeEls.push( c );
					eyeElsSet.add( c );
					HEAD_TOP_LAYER.els = HEAD_TOP_LAYER.els.filter(
						function ( el ) {
							return el !== c;
						}
					);
					COLOR_LAYERS.forEach( function ( layer ) {
						layer.els = layer.els.filter( function ( el ) {
							return el !== c;
						} );
					} );
				} );

			HEAD_TOP_LAYER.els = HEAD_TOP_LAYER.els.filter( function ( el ) {
				return ! eyeElsSet.has( el );
			} );
			COLOR_LAYERS.forEach( function ( layer ) {
				layer.els = layer.els.filter( function ( el ) {
					return ! eyeElsSet.has( el );
				} );
			} );

			highlightEls = [];
			[].slice
				.call( svg.querySelectorAll( 'path' ) )
				.forEach( function ( p ) {
					var bb;
					try {
						bb = p.getBBox();
					} catch ( _ ) {
						return;
					}
					var cx = bb.x + bb.width / 2,
						cy = bb.y + bb.height / 2;
					var nearAnyEye = eyeGroups.some( function ( g ) {
						return (
							Math.sqrt(
								( cx - g.eyeCx ) * ( cx - g.eyeCx ) +
									( cy - g.eyeCy ) * ( cy - g.eyeCy )
							) < 40 &&
							bb.width < 35 &&
							bb.height < 35
						);
					} );
					if ( nearAnyEye ) {
						highlightEls.push( p );
						protectedEls.add( p );
						HEAD_TOP_LAYER.els = HEAD_TOP_LAYER.els.filter(
							function ( el ) {
								return el !== p;
							}
						);
						COLOR_LAYERS.forEach( function ( layer ) {
							layer.els = layer.els.filter( function ( el ) {
								return el !== p;
							} );
						} );
					}
				} );
		}

		var existingBoom = svg.querySelector( '#egg-boom-text' );
		if ( existingBoom ) existingBoom.remove();
		var allClassEls = [];
		v.classes.forEach( function ( sel ) {
			allClassEls = allClassEls.concat(
				[].slice.call( svg.querySelectorAll( sel ) )
			);
		} );
		var svgAll = [].slice.call( svg.querySelectorAll( '*' ) );
		var firstClassEl = null;
		for ( var i = 0; i < svgAll.length; i++ ) {
			if ( allClassEls.indexOf( svgAll[ i ] ) >= 0 ) {
				firstClassEl = svgAll[ i ];
				break;
			}
		}
		var boomText = document.createElementNS(
			'http://www.w3.org/2000/svg',
			'text'
		);
		boomText.id = 'egg-boom-text';
		var boomSc = pinataPxWidth() / active.vbW;
		var boomOffX = active.boomOffsetX !== null ? active.boomOffsetX : 20;
		var boomOffY = active.boomOffsetY !== null ? active.boomOffsetY : 40;
		boomText.setAttribute(
			'x',
			( _contentBB.centerX + boomOffX / boomSc ).toFixed( 1 )
		);
		boomText.setAttribute(
			'y',
			( _contentBB.centerY + boomOffY / boomSc ).toFixed( 1 )
		);
		boomText.setAttribute( 'text-anchor', 'middle' );
		boomText.setAttribute( 'dominant-baseline', 'middle' );
		boomText.setAttribute( 'fill', '#ffffff' );
		boomText.setAttribute(
			'font-size',
			( ( active.vbW * 0.08 ) / ( active.scale || 1.0 ) ).toFixed( 1 )
		);
		boomText.setAttribute( 'font-weight', '500' );
		boomText.setAttribute(
			'font-family',
			'system-ui,-apple-system,"Segoe UI",Roboto,sans-serif'
		);
		boomText.textContent = settings.boomText || 'One down';
		boomText.style.opacity = '0';
		boomText.style.transition = 'opacity 0.6s ease';
		if ( firstClassEl && firstClassEl.parentNode ) {
			firstClassEl.parentNode.insertBefore(
				boomText,
				firstClassEl.nextSibling
			);
		} else {
			svg.appendChild( boomText );
		}

		mainAngle = 0;
		mainOmega = 0;
	}

	/* -- Init --------------------------------------------------------------- */
	/* -- Blink -------------------------------------------------------------- */
	function startBlink() {
		if ( ! eyeGroups.length ) return;
		blinkScaleY = 1;
		function animTo( from, to, dur, done ) {
			var t0 = Date.now();
			( function tick() {
				var p = Math.min( 1, ( Date.now() - t0 ) / dur );
				blinkScaleY = from + ( to - from ) * p;
				if ( p < 1 ) requestAnimationFrame( tick );
				else if ( done ) done();
			} )();
		}
		function doBlink() {
			animTo( 1, 0.05, 80, function () {
				animTo( 0.05, 1, 150, function () {
					blinkTimer = setTimeout(
						doBlink,
						1500 + Math.random() * 2500
					);
				} );
			} );
		}
		blinkTimer = setTimeout( doBlink, 1000 + Math.random() * 1500 );
	}

	function updatePupil() {
		if ( ! eyeGroups.length || ! overlayEl || ! overlayEl.parentNode )
			return;
		var lastOx = 0,
			lastOy = 0;
		eyeGroups.forEach( function ( group ) {
			var ctm;
			try {
				ctm = group.pupilEl.getScreenCTM();
			} catch ( _ ) {
				return;
			}
			if ( ! ctm ) return;
			var scale = Math.sqrt( ctm.a * ctm.a + ctm.b * ctm.b );
			var esx = ctm.a * group.pupilCx + ctm.c * group.pupilCy + ctm.e;
			var esy = ctm.b * group.pupilCx + ctm.d * group.pupilCy + ctm.f;
			var dx = mx - esx,
				dy = my - esy;
			var maxPx = 13 * scale;
			var dist = Math.sqrt( dx * dx + dy * dy );
			if ( dist > maxPx ) {
				var ratio = maxPx / dist;
				dx *= ratio;
				dy *= ratio;
			}
			var ox = dx / scale,
				oy = dy / scale;
			lastOx = ox;
			lastOy = oy;
			var t =
				'translate(' +
				( group.pupilCx + ox ) +
				',' +
				( group.pupilCy + oy ) +
				') scale(1,' +
				blinkScaleY.toFixed( 4 ) +
				') translate(' +
				-group.pupilCx +
				',' +
				-group.pupilCy +
				')';
			group.pupilEl.setAttribute( 'transform', t );
		} );
		highlightEls.forEach( function ( el ) {
			el.setAttribute(
				'transform',
				'translate(' + lastOx + ',' + lastOy + ')'
			);
		} );
	}

	/* -- Physics ------------------------------------------------------------ */
	function update() {
		if ( ! dropped ) return;

		var proxTorque = 0;
		if ( mActive ) {
			var c = pinataCentre(),
				dx = mx - c.x,
				d = Math.hypot( dx, my - c.y ),
				R = c.r * 2.69;
			if ( d < R && d > 1 )
				proxTorque = -( dx / d ) * Math.pow( 1 - d / R, 2 ) * 0.0006;
		}

		mainOmega =
			( mainOmega +
				( -settings.gravity * Math.sin( mainAngle ) + proxTorque ) ) *
			settings.damp;
		if ( mainOmega > 0.06 ) mainOmega = 0.06;
		if ( mainOmega < -0.06 ) mainOmega = -0.06;
		mainAngle += mainOmega;
		var _maxSwing =
			active && active.maxSwing !== null ? active.maxSwing : 0.9;
		if ( mainAngle > _maxSwing ) {
			mainAngle = _maxSwing;
			mainOmega *= -0.4;
		}
		if ( mainAngle < -_maxSwing ) {
			mainAngle = -_maxSwing;
			mainOmega *= -0.4;
		}

		var pcx = _contentBB.centerX,
			pcy = _contentBB.centerY;
		COLOR_LAYERS.forEach( function ( layer ) {
			if ( ! layer.els.length ) return;
			var target =
				-mainOmega *
				layer.velMult *
				settings.sway *
				( 180 / Math.PI ) *
				14;
			layer.vel =
				( layer.vel + ( target - layer.val ) * layer.stiff ) *
				layer.damp;
			layer.val += layer.vel;
			var t =
				'rotate(' +
				layer.val.toFixed( 3 ) +
				' ' +
				pcx +
				' ' +
				pcy +
				')';
			layer.els.forEach( function ( el ) {
				el.setAttribute( 'transform', t );
			} );
		} );

		if ( HEAD_TOP_LAYER.els.length ) {
			var ht =
				-mainOmega *
				HEAD_TOP_LAYER.velMult *
				settings.sway *
				( 180 / Math.PI ) *
				14;
			HEAD_TOP_LAYER.vel =
				( HEAD_TOP_LAYER.vel +
					( ht - HEAD_TOP_LAYER.val ) * HEAD_TOP_LAYER.stiff ) *
				HEAD_TOP_LAYER.damp;
			HEAD_TOP_LAYER.val += HEAD_TOP_LAYER.vel;
			var htT =
				'rotate(' +
				HEAD_TOP_LAYER.val.toFixed( 3 ) +
				' ' +
				pcx +
				' ' +
				pcy +
				')';
			HEAD_TOP_LAYER.els.forEach( function ( el ) {
				el.setAttribute( 'transform', htT );
			} );
		}

		squishVel = ( squishVel - squishAmp * 0.22 ) * 0.72;
		squishAmp += squishVel;
		if ( pinataEl ) {
			var sdeg = ( ( squishAngle * 180 ) / Math.PI ).toFixed( 2 );
			var sAlong = ( 1 - squishAmp * 0.042 ).toFixed( 4 );
			var sPerp = ( 1 + squishAmp * 0.063 ).toFixed( 4 );
			pinataEl.style.transform =
				'rotate(' +
				sdeg +
				'deg) scaleX(' +
				sAlong +
				') scaleY(' +
				sPerp +
				') rotate(' +
				( ( -squishAngle * 180 ) / Math.PI ).toFixed( 2 ) +
				'deg)';
		}
	}

	function applyRotation() {
		document.getElementById( 'egg-pendulum' ).style.transform =
			'rotate(' + mainAngle + 'rad)';
	}

	/* -- Canvases ----------------------------------------------------------- */
	function sizeCanvasHiDPI( canvas ) {
		var dpr = window.devicePixelRatio || 1,
			w = window.innerWidth,
			h = window.innerHeight;
		var tw = Math.round( w * dpr ),
			th = Math.round( h * dpr );
		if ( canvas.width !== tw || canvas.height !== th ) {
			canvas.width = tw;
			canvas.height = th;
		}
		return dpr;
	}

	function sizeConfetti() {
		sizeCanvasHiDPI( cfCV );
	}

	/* -- Confetti ----------------------------------------------------------- */
	async function loadConfettiShapes() {
		var text = SVG_DATA.confetti;
		if ( ! text ) return;
		var tmp = document.createElement( 'div' );
		tmp.style.cssText =
			'position:absolute;left:-9999px;top:-9999px;visibility:hidden';
		tmp.innerHTML = text;
		document.body.appendChild( tmp );
		[].slice
			.call( tmp.querySelectorAll( 'path' ) )
			.forEach( function ( p ) {
				var bb;
				try {
					bb = p.getBBox();
				} catch ( _ ) {
					return;
				}
				var d = p.getAttribute( 'd' ),
					fill = p.getAttribute( 'fill' ) || '#7c5cf0';
				if ( ! d || bb.width <= 0 || bb.height <= 0 ) return;
				var aspect =
					Math.max( bb.width, bb.height ) /
					Math.max( 0.0001, Math.min( bb.width, bb.height ) );
				var kind = 'misc';
				if ( aspect < 1.15 ) {
					kind = 'circle';
				} else if ( aspect > 1.7 ) {
					kind = 'line';
				}
				ALL_SHAPES.push( {
					path2d: new Path2D( d ),
					cx: bb.x + bb.width / 2,
					cy: bb.y + bb.height / 2,
					r: Math.max( bb.width, bb.height ) / 2,
					color: fill,
					kind,
				} );
			} );
		document.body.removeChild( tmp );
	}

	function filterShapes() {
		var k = settings.cfShape;
		if ( k === 'circles' ) {
			activeShapes = ALL_SHAPES.filter( function ( s ) {
				return s.kind === 'circle';
			} );
		} else if ( k === 'lines' ) {
			activeShapes = ALL_SHAPES.filter( function ( s ) {
				return s.kind === 'line';
			} );
		} else {
			activeShapes = ALL_SHAPES.slice();
		}
		if ( ! activeShapes.length ) activeShapes = ALL_SHAPES.slice();
	}

	function rebuildColumns() {
		columns = new Array(
			Math.ceil( window.innerWidth / COLUMN_W ) + 4
		).fill( window.innerHeight );
	}

	function colOf( x ) {
		return Math.max(
			0,
			Math.min( columns.length - 1, Math.floor( x / COLUMN_W ) )
		);
	}

	function spawnBurst( x, y, count, opts ) {
		if ( ! settings.cfOn || ! activeShapes.length ) return;
		count = count || settings.cfCount;
		var upBias = ( opts && opts.upBias ) || 2.5;
		for ( var i = 0; i < count; i++ ) {
			var ang = Math.random() * Math.PI * 2,
				speed = 5 + Math.random() * 8;
			var tpl =
				activeShapes[
					Math.floor( Math.random() * activeShapes.length )
				];
			var scale =
				( 6 / Math.max( 0.5, tpl.r ) ) *
				( 0.85 + Math.random() * 0.3 ) *
				settings.cfSize;
			live.push( {
				x: x + ( Math.random() - 0.5 ) * 10,
				y: y + ( Math.random() - 0.5 ) * 10,
				vx: Math.cos( ang ) * speed,
				vy: Math.sin( ang ) * speed - upBias,
				rot: Math.random() * Math.PI * 2,
				rotVel: ( Math.random() - 0.5 ) * 0.18,
				scale,
				tpl,
				r: tpl.r * scale,
			} );
		}
	}

	function updateConfetti() {
		var H = window.innerHeight;
		for ( var i = live.length - 1; i >= 0; i-- ) {
			var p = live[ i ];
			p.vy += 0.36;
			p.vx *= 0.992;
			p.rotVel *= 0.992;
			p.x += p.vx;
			p.y += p.vy;
			p.rot += p.rotVel;
			if ( p.x < -p.r ) {
				p.x = -p.r;
				p.vx = -p.vx * 0.4;
			}
			if ( p.x > window.innerWidth + p.r ) {
				p.x = window.innerWidth + p.r;
				p.vx = -p.vx * 0.4;
			}

			if ( ! settings.cfPile ) {
				if ( p.y - p.r > H + 50 ) live.splice( i, 1 );
				continue;
			}

			var r = p.r,
				r2 = r * 2,
				cc = colOf( p.x ),
				hc = Math.ceil( r / COLUMN_W ),
				ftop = H;
			for ( var dc = -hc; dc <= hc; dc++ ) {
				var nc = cc + dc;
				if ( nc >= 0 && nc < columns.length && columns[ nc ] < ftop )
					ftop = columns[ nc ];
			}

			if ( p.y + r >= ftop ) {
				var fc = cc,
					fy = columns[ cc ],
					rr = Math.max( 2, hc + 1 );
				for ( var dc2 = -rr; dc2 <= rr; dc2++ ) {
					if ( ! dc2 ) continue;
					var nc2 = cc + dc2;
					if (
						nc2 >= 0 &&
						nc2 < columns.length &&
						columns[ nc2 ] > fy + 1
					) {
						fc = nc2;
						fy = columns[ nc2 ];
					}
				}
				if ( fc !== cc )
					p.x =
						( fc + 0.5 ) * COLUMN_W +
						( Math.random() - 0.5 ) * COLUMN_W;
				p.y = fy - r;
				p.rotVel = 0;
				var sp = hc + 2;
				for ( var dc3 = -sp; dc3 <= sp; dc3++ ) {
					var nc3 = fc + dc3;
					if ( nc3 < 0 || nc3 >= columns.length ) continue;
					var dx3 = dc3 * COLUMN_W;
					if ( Math.abs( dx3 ) > r2 ) continue;
					var nt =
						p.y +
						r -
						Math.sqrt( Math.max( 0, r2 * r2 - dx3 * dx3 ) );
					if ( nt < columns[ nc3 ] ) columns[ nc3 ] = nt;
				}
				settled.push( p );
				live.splice( i, 1 );
				if ( settled.length > SETTLED_CAP ) settled.splice( 0, 60 );
			} else if ( p.y - p.r > H + 50 ) {
				live.splice( i, 1 );
			}
		}
	}

	function drawShapeList( arr ) {
		for ( var i = 0; i < arr.length; i++ ) {
			var p = arr[ i ],
				tpl = p.tpl;
			cfCtx.save();
			cfCtx.translate( p.x, p.y );
			cfCtx.rotate( p.rot );
			cfCtx.scale( p.scale, p.scale );
			cfCtx.translate( -tpl.cx, -tpl.cy );
			cfCtx.fillStyle = tpl.color;
			cfCtx.fill( tpl.path2d );
			cfCtx.restore();
		}
	}

	function drawFallingPieces() {
		for ( var i = 0; i < fallingPieces.length; i++ ) {
			var p = fallingPieces[ i ];
			cfCtx.save();
			cfCtx.translate( p.x, p.y );
			cfCtx.rotate( p.rot );
			cfCtx.scale( p.scale, p.scale );
			cfCtx.translate( -p.svgCx, -p.svgCy );
			cfCtx.fillStyle = p.fill;
			cfCtx.fill( p.path2d );
			cfCtx.restore();
		}
	}

	function drawConfetti() {
		var dpr = sizeCanvasHiDPI( cfCV );
		cfCtx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		cfCtx.clearRect( 0, 0, window.innerWidth, window.innerHeight );
		drawShapeList( settled );
		drawShapeList( live );
		drawFallingPieces();
	}

	function drawBg() {
		var dpr = sizeCanvasHiDPI( bgCV );
		bgCtx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		bgCtx.clearRect( 0, 0, window.innerWidth, window.innerHeight );
		if ( ! dropped ) return;
		var c = pinataCentre();
		var strOff =
			active && active.stringOffsetX !== null ? active.stringOffsetX : 10;
		var topX = window.innerWidth / 2 + strOff,
			topY = 0;
		var botX = c.x + strOff,
			botY = c.y;
		var trail = mainOmega * 1200;
		var cp1x = topX + ( botX - topX ) * 0.25 - trail * 0.2;
		var cp1y = topY + ( botY - topY ) * 0.25;
		var cp2x = topX + ( botX - topX ) * 0.75 - trail * 0.8;
		var cp2y = topY + ( botY - topY ) * 0.75;
		bgCtx.beginPath();
		bgCtx.moveTo( topX, topY );
		bgCtx.bezierCurveTo( cp1x, cp1y, cp2x, cp2y, botX, botY );
		bgCtx.strokeStyle = '#330862';
		bgCtx.lineWidth = 4;
		bgCtx.lineCap = 'round';
		bgCtx.stroke();
	}

	/* -- Falling pinata pieces ---------------------------------------------- */
	function detachPiece() {
		var allLayers = COLOR_LAYERS.filter( function ( l ) {
			return l.els.length > 0;
		} );
		if ( HEAD_TOP_LAYER && HEAD_TOP_LAYER.els.length > 0 )
			allLayers.push( HEAD_TOP_LAYER );

		var eligible = [];
		allLayers.forEach( function ( layer ) {
			layer.els.forEach( function ( el ) {
				if ( ! protectedEls.has( el ) ) eligible.push( { layer, el } );
			} );
		} );
		if ( ! eligible.length ) return;

		var pick = eligible[ Math.floor( Math.random() * eligible.length ) ];
		var layer = pick.layer;
		var el = pick.el;
		var idx = layer.els.indexOf( el );

		var d = el.getAttribute( 'd' );
		var fill =
			window.getComputedStyle( el ).fill ||
			el.getAttribute( 'fill' ) ||
			'#c084fc';
		var bb, ctm;
		try {
			bb = el.getBBox();
		} catch ( _ ) {
			bb = null;
		}
		try {
			ctm = el.getScreenCTM();
		} catch ( _ ) {
			ctm = null;
		}

		layer.els.splice( idx, 1 );
		if ( el.parentNode ) el.parentNode.removeChild( el );

		if ( ! d || ! bb || ! ctm || bb.width === 0 ) return;

		var svgCx = bb.x + bb.width / 2;
		var svgCy = bb.y + bb.height / 2;
		var sx = ctm.a * svgCx + ctm.c * svgCy + ctm.e;
		var sy = ctm.b * svgCx + ctm.d * svgCy + ctm.f;
		var scale = Math.sqrt( ctm.a * ctm.a + ctm.b * ctm.b );

		fallingPieces.push( {
			path2d: new Path2D( d ),
			fill,
			svgCx,
			svgCy,
			x: sx,
			y: sy,
			vx: ( Math.random() - 0.5 ) * 9,
			vy: -5 - Math.random() * 5,
			rot: Math.atan2( ctm.b, ctm.a ),
			rotVel: ( Math.random() - 0.5 ) * 0.14,
			scale,
		} );
	}

	function updateFallingPieces() {
		for ( var i = fallingPieces.length - 1; i >= 0; i-- ) {
			var p = fallingPieces[ i ];
			p.vy += 0.5;
			p.vx *= 0.992;
			p.x += p.vx;
			p.y += p.vy;
			p.rot += p.rotVel;
			if ( p.y > window.innerHeight + 300 ) fallingPieces.splice( i, 1 );
		}
	}

	/* -- Cursor ------------------------------------------------------------- */
	function updateCursor() {
		if ( ! mActive ) return;
		var rawVx = mx - lastMx;
		mouseVx = mouseVx * 0.7 + rawVx * 0.3;
		lastMx = mx;
		var vel = Math.max( -35, Math.min( 35, mouseVx * 0.45 ) );
		stickAngleVel =
			( stickAngleVel + ( 25 + vel - stickAngle ) * 0.1 ) * 0.82;
		stickAngle += stickAngleVel;
		stick.style.transform =
			'translate(' +
			( mx - 65 ) +
			'px,' +
			( my - 73 ) +
			'px) rotate(' +
			stickAngle.toFixed( 2 ) +
			'deg)';
		var near =
			dropped &&
			Math.hypot( mx - pinataCentre().x, my - pinataCentre().y ) <
				pinataCentre().r * 1.68;
		stick.style.opacity = near ? 1 : 0;
		overlayEl.classList.toggle( 'near-pinata', near );
	}

	function swingStick() {
		stickAngleVel -= 55;
	}

	/* -- Hits --------------------------------------------------------------- */
	function tryHit( cx, cy ) {
		if ( ! dropped ) return;
		var c = pinataCentre();
		if ( Math.hypot( cx - c.x, cy - c.y ) > c.r * 1.68 ) return;
		mainOmega +=
			( c.x - cx ) * settings.hit +
			( Math.random() - 0.5 ) * settings.hit * 5;
		COLOR_LAYERS.forEach( function ( l ) {
			l.vel += ( Math.random() - 0.5 ) * 4.5;
		} );
		spawnBurst( cx, cy );
		swingStick();
		detachPiece();
		squishAngle = Math.atan2( c.y - cy, c.x - cx );
		squishVel += 0.9;
		var boomEl = document.getElementById( 'egg-boom-text' );
		if ( boomEl && boomEl.style.opacity === '0' )
			boomEl.style.opacity = '1';
	}

	/* -- Main loop ---------------------------------------------------------- */
	function loop() {
		update();
		applyRotation();
		drawBg();
		updateConfetti();
		updateFallingPieces();
		drawConfetti();
		updateCursor();
		updatePupil();
		rafId = requestAnimationFrame( loop );
	}

	async function init() {
		stick.innerHTML = SVG_DATA.stick || '';
		ALL_SHAPES = [];
		if ( ! reducedMotion ) await loadConfettiShapes();
		filterShapes();
		await loadPinata( settings.variant );
		if ( ! reducedMotion ) startBlink();
		dropped = true;
		if ( reducedMotion ) {
			applyRotation();
			drawBg();
		} else {
			mainOmega = 0.022;
			loop();
		}
	}

	/* -- Named event handlers (for cleanup) --------------------------------- */
	function onMouseMove( e ) {
		mx = e.clientX;
		my = e.clientY;
		mActive = true;
	}
	function onMouseLeave() {
		mActive = false;
	}
	function onMouseEnter() {
		mActive = true;
	}
	function onResize() {
		applyLayout();
		sizeConfetti();
		rebuildColumns();
		settled.length = 0;
	}

	function onKeyDown( e ) {
		if ( e.key === 'Escape' ) {
			closeOverlay();
			return;
		}
		if ( e.key === 'Tab' && overlayEl ) {
			var focusable = [].slice.call(
				overlayEl.querySelectorAll(
					'button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])'
				)
			);
			if ( ! focusable.length ) return;
			var first = focusable[ 0 ],
				last = focusable[ focusable.length - 1 ];
			var activeElement = overlayEl.ownerDocument.activeElement;
			if ( e.shiftKey ) {
				if ( activeElement === first ) {
					e.preventDefault();
					last.focus();
				}
			} else if ( activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	}

	function onDocClick( e ) {
		var t = e.target;
		if ( ! t || ! t.closest ) {
			tryHit( e.clientX, e.clientY );
			return;
		}
		if ( t.closest( '#egg-close-btn' ) ) return;
		if ( t.closest( '.egg-celebrate-btn' ) ) return;
		if ( t.closest( '.egg-opt-out-btn' ) ) return;
		tryHit( e.clientX, e.clientY );
	}

	/* -- Close -------------------------------------------------------------- */
	closeOverlay = function () {
		if ( currentMilestone && currentMilestone._orderId && DISMISS ) {
			var fd = new FormData();
			fd.append( 'action', 'wc_egg_dismiss' );
			fd.append( 'nonce', DISMISS.nonce );
			fd.append( 'order_id', currentMilestone._orderId );
			if ( navigator.sendBeacon ) {
				navigator.sendBeacon( DISMISS.url, fd );
			} else {
				fetch( DISMISS.url, { method: 'POST', body: fd } );
			}
		}
		if ( blinkTimer ) {
			clearTimeout( blinkTimer );
			blinkTimer = null;
		}
		if ( rafId ) {
			cancelAnimationFrame( rafId );
			rafId = null;
		}
		document.removeEventListener( 'mousemove', onMouseMove );
		document.removeEventListener( 'mouseleave', onMouseLeave );
		document.removeEventListener( 'mouseenter', onMouseEnter );
		document.removeEventListener( 'click', onDocClick );
		document.removeEventListener( 'keydown', onKeyDown );
		window.removeEventListener( 'resize', onResize );
		if ( svgDefsEl && svgDefsEl.parentNode ) svgDefsEl.remove();
		if ( overlayEl && overlayEl.parentNode ) overlayEl.remove();
		var st = document.getElementById( 'woo-egg-style' );
		if ( st ) st.remove();
		if ( _previousFocus && typeof _previousFocus.focus === 'function' ) {
			_previousFocus.focus();
		}
	};

	/* -- Build overlay DOM -------------------------------------------------- */
	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function buildOverlay( milestoneData ) {
		var W = window.innerWidth,
			H = window.innerHeight;

		var clipId = 'woo-egg-clip-' + Date.now();
		svgDefsEl = document.createElementNS(
			'http://www.w3.org/2000/svg',
			'svg'
		);
		svgDefsEl.setAttribute(
			'style',
			'position:fixed;width:0;height:0;overflow:hidden;'
		);
		svgDefsEl.setAttribute( 'aria-hidden', 'true' );
		svgDefsEl.innerHTML =
			'<defs><clipPath id="' +
			clipId +
			'" clipPathUnits="userSpaceOnUse">' +
			'<path d="M307.663 146.15C359.341 75.0814 488.669 81.0479 596.526 159.476C704.382 237.904 749.924 359.096 698.247 430.164C651.519 494.425 541.305 495.701 440.882 437.375C449.291 461.059 449.707 484.793 440.392 505.903C429.177 531.317 405.648 548.879 375.294 557.678C396.625 569.611 410.777 587.78 412.436 609.027C415.632 649.967 371.235 686.824 313.274 691.349C255.312 695.874 205.734 666.353 202.537 625.413C200.224 595.777 222.849 568.282 257.506 553.641C246.771 550.423 235.976 546.454 225.237 541.715C133.082 501.051 79.9982 419.086 106.67 358.642C133.178 298.571 228.485 282.367 320.121 322.088C281.506 259.241 274.191 192.182 307.663 146.15Z"' +
			' transform="translate(' +
			W / 2 +
			' ' +
			H / 2 +
			') scale(0) translate(-391 -381)"/>' +
			'</clipPath></defs>';
		document.body.appendChild( svgDefsEl );
		var wooblePath = svgDefsEl.querySelector( 'path' );

		var style = document.createElement( 'style' );
		style.id = 'woo-egg-style';
		style.textContent =
			'#woo-egg-overlay,#woo-egg-overlay *{box-sizing:border-box;margin:0;padding:0}' +
			'#woo-egg-overlay{position:fixed;inset:0;background:#fff;z-index:999999;overflow:visible;cursor:default;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Oxygen-Sans",Ubuntu,Cantarell,"Helvetica Neue",sans-serif;color:#1e1e1e;clip-path:url(#' +
			clipId +
			');--wp-components-color-accent:#3858e9;--wp-components-color-accent-darker-10:#2145e6;--wp-components-color-accent-darker-20:#183ad6;--wp-components-color-accent-inverted:#fff}' +
			'#woo-egg-overlay .egg-celebrate-btn.components-button{padding:6px 12px;cursor:pointer}' +
			'#woo-egg-overlay .egg-celebrate-btn.components-button:active:not(:disabled){background:var(--wp-components-color-accent-darker-20,#183ad6);color:var(--wp-components-color-accent-inverted,#fff)}' +
			'#woo-egg-overlay .egg-opt-out-btn.components-button:active:not(:disabled){color:var(--wp-components-color-accent,#3858e9)}' +
			'#woo-egg-overlay.near-pinata,#woo-egg-overlay.near-pinata .egg-celebrate-btn{cursor:none}' +
			'#woo-egg-overlay.near-pinata *{user-select:none}' +
			'#egg-scene{position:absolute;inset:0;overflow:visible}' +
			'#egg-bg,#egg-confetti{position:absolute;inset:0;width:100%;height:100%}' +
			'#egg-confetti{pointer-events:none;z-index:2}' +
			'#egg-drop-wrapper{position:absolute;top:0;left:50%;transform:translate(-50%,0);will-change:transform;pointer-events:none;z-index:1;overflow:visible}' +
			'#egg-pendulum{position:relative;transform-origin:top center;will-change:transform;pointer-events:auto;overflow:visible}' +
			'#egg-rope{width:0;margin:0 auto}' +
			'#egg-pinata-container{position:relative;margin:0 auto;pointer-events:auto;overflow:visible}' +
			'#egg-pinata-container svg{display:block;width:100%;height:auto;pointer-events:auto;transform-origin:center center;overflow:visible}' +
			'#woo-egg-overlay .egg-copy{position:fixed;left:50%;transform:translateX(-50%);text-align:center;pointer-events:auto;width:min(560px,calc(100vw - 32px));z-index:3}' +
			'#woo-egg-overlay .egg-copy h1{font-size:clamp(20px,2.4vw,32px);font-weight:500;color:#1e1e1e;margin-bottom:24px;letter-spacing:0}' +
			'#woo-egg-overlay .egg-copy p{font-size:15px;color:#707070;line-height:24px;margin-bottom:32px;max-width:100%;margin-left:auto;margin-right:auto;white-space:pre-line}' +
			'#egg-cursor-stick{position:fixed;top:0;left:0;pointer-events:none;z-index:9999;will-change:transform,opacity;transition:opacity 120ms ease;width:91px;height:91px;transform-origin:80% 90%;opacity:0}' +
			'#egg-cursor-stick svg{width:100%;height:100%;display:block}' +
			'#egg-close-btn{position:fixed;top:16px;right:16px;width:32px;height:32px;border:1px solid #ddd;border-radius:4px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#1e1e1e;z-index:1000000;transition:background 120ms ease,border-color 120ms ease;padding:0}' +
			'#egg-close-btn svg{width:24px;height:24px;fill:currentColor;display:block}' +
			'#egg-close-btn:hover{background:#f0f0f0;border-color:#949494}' +
			'#egg-sprinkle-bg{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}' +
			'#egg-sprinkle-bg svg{width:100%;height:100%;display:block}' +
			'@keyframes egg-particle-pulse{0%,100%{opacity:1}50%{opacity:0.15}}' +
			'#woo-egg-overlay .egg-actions{display:flex;flex-direction:column;align-items:center;gap:8px}' +
			'#woo-egg-overlay .egg-opt-out-btn.components-button{padding:6px 12px}' +
			'@media (prefers-reduced-motion: reduce){#egg-sprinkle-bg *{animation:none!important}#egg-pinata-container svg{transition:none!important}}';
		document.head.appendChild( style );

		var el = document.createElement( 'div' );
		el.id = 'woo-egg-overlay';
		el.innerHTML =
			'<div id="egg-scene">' +
			'<canvas id="egg-bg"></canvas>' +
			'<div id="egg-drop-wrapper">' +
			'<div id="egg-pendulum">' +
			'<div id="egg-rope"></div>' +
			'<div id="egg-pinata-container"></div>' +
			'</div>' +
			'</div>' +
			'<canvas id="egg-confetti"></canvas>' +
			'<div class="egg-copy">' +
			'<h1 id="egg-headline" class="egg-headline">' +
			escHtml( milestoneData.title ) +
			'</h1>' +
			'<p class="egg-body">' +
			escHtml( milestoneData.subtitle ) +
			'</p>' +
			'<div class="egg-actions">' +
			'<button class="egg-celebrate-btn components-button is-primary">' +
			escHtml( LABELS.cta ) +
			'</button>' +
			'<button class="egg-opt-out-btn components-button is-tertiary">' +
			escHtml( LABELS.optOut ) +
			'</button>' +
			'</div>' +
			'</div>' +
			'<div id="egg-cursor-stick"></div>' +
			'</div>' +
			'<button id="egg-close-btn" aria-label="' +
			escHtml( LABELS.closeLabel ) +
			'" title="' +
			escHtml( LABELS.closeTitle ) +
			'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"/></svg></button>';

		if ( SVG_DATA.sprinkle ) {
			var sp = document.createElement( 'div' );
			sp.id = 'egg-sprinkle-bg';
			sp.innerHTML = SVG_DATA.sprinkle;
			var spSvg = sp.querySelector( 'svg' );
			if ( spSvg ) {
				spSvg.setAttribute( 'preserveAspectRatio', 'xMidYMid slice' );
				var particles = spSvg.querySelectorAll( 'path,circle,rect' );
				particles.forEach( function ( p ) {
					var dur = ( 3 + Math.random() * 5 ).toFixed( 2 );
					var delay = -( Math.random() * 8 ).toFixed( 2 );
					p.style.animation =
						'egg-particle-pulse ' +
						dur +
						's ease-in-out ' +
						delay +
						's infinite';
				} );
			}
			var scene = el.querySelector( '#egg-scene' );
			scene.insertBefore( sp, scene.firstChild );
		}

		el.setAttribute( 'role', 'dialog' );
		el.setAttribute( 'aria-modal', 'true' );
		el.setAttribute( 'aria-labelledby', 'egg-headline' );
		document.body.appendChild( el );
		overlayEl = el;

		var firstFocusable = el.querySelector(
			'.egg-celebrate-btn, #egg-close-btn'
		);
		if ( firstFocusable ) firstFocusable.focus();

		bgCV = document.getElementById( 'egg-bg' );
		bgCtx = bgCV.getContext( '2d' );
		cfCV = document.getElementById( 'egg-confetti' );
		cfCtx = cfCV.getContext( '2d' );
		stick = document.getElementById( 'egg-cursor-stick' );
		sizeConfetti();
		rebuildColumns();

		el.querySelector( '.egg-celebrate-btn' ).addEventListener(
			'click',
			function ( e ) {
				var r = e.currentTarget.getBoundingClientRect();
				spawnBurst(
					r.left + r.width / 2,
					r.top + r.height / 2,
					settings.cfCount + 8,
					{ upBias: 7 }
				);
				swingStick();
				closeOverlay();
			}
		);

		document
			.getElementById( 'egg-close-btn' )
			.addEventListener( 'click', closeOverlay );

		el.querySelector( '.egg-opt-out-btn' ).addEventListener(
			'click',
			function () {
				if ( DISMISS ) {
					var fd = new FormData();
					fd.append( 'action', 'wc_egg_opt_out' );
					fd.append( 'nonce', DISMISS.nonce );
					if ( navigator.sendBeacon ) {
						navigator.sendBeacon( DISMISS.url, fd );
					} else {
						fetch( DISMISS.url, { method: 'POST', body: fd } );
					}
				}
				closeOverlay();
			}
		);

		document.addEventListener( 'mousemove', onMouseMove );
		document.addEventListener( 'mouseleave', onMouseLeave );
		document.addEventListener( 'mouseenter', onMouseEnter );
		document.addEventListener( 'click', onDocClick );
		document.addEventListener( 'keydown', onKeyDown );
		window.addEventListener( 'resize', onResize );

		var maxScale = Math.sqrt( W * W + H * H ) / 220;
		var revealStart = Date.now();
		( function animReveal() {
			var t = Math.min( ( Date.now() - revealStart ) / 1000, 1 );
			var e2 =
				t < 0.5 ? 4 * t * t * t : 1 - Math.pow( -2 * t + 2, 3 ) / 2;
			wooblePath.setAttribute(
				'transform',
				'translate(' +
					W / 2 +
					' ' +
					H / 2 +
					') scale(' +
					e2 * maxScale +
					') translate(-391 -381)'
			);
			if ( t < 1 ) requestAnimationFrame( animReveal );
			else overlayEl.style.clipPath = 'none';
		} )();
	}

	/* -- showOverlay -------------------------------------------------------- */
	function showOverlay( milestoneData ) {
		mainAngle = 0;
		mainOmega = 0;
		dropped = false;
		mouseVx = 0;
		mActive = false;
		stickAngle = 25;
		stickAngleVel = 0;
		live.length = 0;
		settled.length = 0;
		fallingPieces = [];
		protectedEls = new Set();
		eyeEls = [];
		eyeElsSet = null;
		blinkScaleY = 1;
		highlightEls = [];
		eyeGroups = [];
		pinataEl = null;
		squishAmp = 0;
		squishVel = 0;
		squishAngle = 0;
		if ( blinkTimer ) {
			clearTimeout( blinkTimer );
			blinkTimer = null;
		}
		ALL_SHAPES = [];
		activeShapes = [];
		COLOR_LAYERS = [];
		HEAD_TOP_LAYER = {
			stiff: 0.038,
			damp: 0.78,
			velMult: 0.06,
			els: [],
			val: 0,
			vel: 0,
		};
		_contentBB = { topY: 0, centerY: 0, centerX: 0 };
		mx = window.innerWidth / 2;
		my = window.innerHeight / 2;
		lastMx = mx;

		currentMilestone = milestoneData;
		settings = Object.assign( {}, DEFAULTS, {
			headline: milestoneData.title,
			body: milestoneData.subtitle,
			variant: milestoneData.variant || 'llama',
			boomText: milestoneData.boomText || 'One down',
		} );

		_previousFocus = document.body.ownerDocument.activeElement;
		buildOverlay( milestoneData );
		init();
	}

	/* -- Milestone URL detection -------------------------------------------- */
	function checkUrl() {
		var params = new URLSearchParams( window.location.search );
		var testKey = params.get( 'woo_egg' );
		if ( testKey && ALL_MILESTONES[ testKey ] ) {
			if ( ! shown[ '__test__' + testKey ] ) {
				shown[ '__test__' + testKey ] = true;
				showOverlay( ALL_MILESTONES[ testKey ] );
			}
			return;
		}
		if (
			params.get( 'page' ) !== 'wc-orders' ||
			params.get( 'action' ) !== 'edit'
		)
			return;
		var orderId = parseInt( params.get( 'id' ), 10 );
		if ( ! orderId || shown[ orderId ] || ! milestones[ orderId ] ) return;
		shown[ orderId ] = true;
		showOverlay(
			Object.assign( {}, milestones[ orderId ], { _orderId: orderId } )
		);
	}

	window.addEventListener( 'load', checkUrl );
	var _push = history.pushState;
	history.pushState = function () {
		_push.apply( this, arguments );
		setTimeout( checkUrl, 200 );
	};
	var _replace = history.replaceState;
	history.replaceState = function () {
		_replace.apply( this, arguments );
		setTimeout( checkUrl, 200 );
	};
	window.addEventListener( 'popstate', checkUrl );
	/* eslint-enable no-var */
} )();
