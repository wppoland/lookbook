/**
 * Lookbook — front-end behaviour (progressive, dependency-free).
 *
 * The product cards use the native Popover API, so the browser handles open,
 * close, light-dismiss and Escape for free. This script only:
 *   1. anchors each open card near its marker (so it does not cover the product), and
 *   2. provides a small click/keyboard fallback for browsers without Popover.
 *
 * Loaded with `defer`. With JS disabled the markers still link nowhere harmful
 * and the page is unaffected.
 */
( function () {
	'use strict';

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		HTMLElement.prototype.hasOwnProperty( 'popover' );

	function positionCard( marker, card ) {
		if ( ! marker || ! card ) {
			return;
		}

		var rect = marker.getBoundingClientRect();
		var cardRect = card.getBoundingClientRect();
		var margin = 12;

		var left = rect.left + rect.width / 2 - cardRect.width / 2;
		var top = rect.bottom + margin;

		left = Math.max(
			margin,
			Math.min( left, window.innerWidth - cardRect.width - margin )
		);

		if ( top + cardRect.height > window.innerHeight - margin ) {
			top = rect.top - cardRect.height - margin;
		}

		top = Math.max( margin, top );

		card.style.position = 'fixed';
		card.style.margin = '0';
		card.style.left = Math.round( left ) + 'px';
		card.style.top = Math.round( top ) + 'px';
	}

	function closeAll( root ) {
		root.querySelectorAll( '.lookbook__marker[aria-expanded="true"]' ).forEach(
			function ( marker ) {
				marker.setAttribute( 'aria-expanded', 'false' );
				var card = marker.parentNode.querySelector( '.lookbook__card' );
				if ( card ) {
					card.hidden = true;
				}
			}
		);

		if ( supportsPopover ) {
			root
				.querySelectorAll( '.lookbook__card:popover-open' )
				.forEach( function ( card ) {
					if ( typeof card.hidePopover === 'function' ) {
						card.hidePopover();
					}
				} );
		}
	}

	function pauseVideos( root ) {
		root.querySelectorAll( '.lookbook__video' ).forEach( function ( video ) {
			if ( typeof video.pause === 'function' ) {
				video.pause();
			}
		} );
	}

	function playActiveSceneVideos( root, index ) {
		var scene = root.querySelector(
			'[data-lookbook-scene][data-scene-index="' + index + '"]'
		);

		if ( ! scene ) {
			return;
		}

		scene.querySelectorAll( '.lookbook__video' ).forEach( function ( video ) {
			if (
				typeof video.play === 'function' &&
				! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
			) {
				video.play().catch( function () {
					// Autoplay may be blocked; ignore.
				} );
			}
		} );
	}

	function hotspotDetail( marker ) {
		return {
			lookbookId: parseInt( marker.getAttribute( 'data-lookbook-id' ), 10 ) || 0,
			sceneIndex:
				parseInt( marker.getAttribute( 'data-scene-index' ), 10 ) || 0,
			hotspotIndex:
				parseInt( marker.getAttribute( 'data-hotspot-index' ), 10 ) || 0,
			productId: parseInt( marker.getAttribute( 'data-product-id' ), 10 ) || 0,
		};
	}

	function dispatchHotspotOpened( root, marker ) {
		if ( typeof CustomEvent !== 'function' ) {
			return;
		}

		var detail = hotspotDetail( marker );
		root.dispatchEvent(
			new CustomEvent( 'lookbook:hotspot-opened', {
				bubbles: true,
				detail: detail,
			} )
		);
		document.dispatchEvent(
			new CustomEvent( 'lookbook:hotspot-opened', {
				detail: detail,
			} )
		);
	}

	function activateScene( root, index ) {
		var tabs = root.querySelectorAll( '[data-lookbook-tab]' );
		var scenes = root.querySelectorAll( '[data-lookbook-scene]' );

		closeAll( root );
		pauseVideos( root );

		tabs.forEach( function ( tab ) {
			var active =
				parseInt( tab.getAttribute( 'data-scene-index' ), 10 ) === index;
			tab.classList.toggle( 'is-active', active );
			tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
		} );

		scenes.forEach( function ( scene ) {
			var active =
				parseInt( scene.getAttribute( 'data-scene-index' ), 10 ) ===
				index;
			scene.classList.toggle( 'is-active', active );
			scene.hidden = ! active;
		} );

		playActiveSceneVideos( root, index );
	}

	function initScene( scene ) {
		var root = scene.closest( '[data-lookbook]' );
		if ( ! root ) {
			return;
		}

		var hotspots = scene.querySelectorAll( '.lookbook__hotspot' );

		hotspots.forEach( function ( hotspot ) {
			var marker = hotspot.querySelector( '.lookbook__marker' );
			var card = hotspot.querySelector( '.lookbook__card' );

			if ( ! marker || ! card ) {
				return;
			}

			if ( supportsPopover ) {
				card.addEventListener( 'toggle', function ( event ) {
					if ( event.newState === 'open' ) {
						positionCard( marker, card );
						dispatchHotspotOpened( root, marker );
					}
				} );
				return;
			}

			marker.setAttribute( 'aria-expanded', 'false' );
			card.classList.add( 'lookbook__card--fallback' );
			card.hidden = true;

			marker.addEventListener( 'click', function () {
				var open = marker.getAttribute( 'aria-expanded' ) === 'true';
				closeAll( root );
				if ( ! open ) {
					marker.setAttribute( 'aria-expanded', 'true' );
					card.hidden = false;
					positionCard( marker, card );
					dispatchHotspotOpened( root, marker );
				}
			} );
		} );
	}

	function initRoot( root ) {
		root.querySelectorAll( '[data-lookbook-scene]' ).forEach( initScene );

		root.querySelectorAll( '[data-lookbook-tab]' ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var index = parseInt(
					tab.getAttribute( 'data-scene-index' ),
					10
				);
				activateScene( root, index );
			} );
		} );

		if ( ! supportsPopover ) {
			document.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Escape' ) {
					closeAll( root );
				}
			} );

			document.addEventListener( 'click', function ( event ) {
				if ( ! event.target.closest( '[data-lookbook]' ) ) {
					closeAll( root );
				}
			} );
		}
	}

	function init() {
		document.querySelectorAll( '[data-lookbook]' ).forEach( initRoot );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.addEventListener( 'resize', function () {
		document
			.querySelectorAll( '.lookbook__card:popover-open' )
			.forEach( function ( card ) {
				var hotspot = card.closest( '.lookbook__hotspot' );
				var marker = hotspot
					? hotspot.querySelector( '.lookbook__marker' )
					: null;
				positionCard( marker, card );
			} );
	} );
} )();
