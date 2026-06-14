/**
 * Lookbook — hotspot editor (meta box) enhancements.
 *
 * Progressive, dependency-free. Adds/removes repeater rows, renumbers them and
 * keeps the input names contiguous, and paints a live pin on the image preview
 * for each hotspot as the X/Y values change. With JS disabled the rows printed
 * by PHP still save normally — this only improves the authoring experience.
 */
( function () {
	'use strict';

	var editor = document.querySelector( '[data-lookbook-editor]' );

	if ( ! editor ) {
		return;
	}

	var i18n =
		( window.lookbookEditor && window.lookbookEditor.i18n ) || {};
	var body = editor.querySelector( '[data-lookbook-rows-body]' );
	var preview = editor.querySelector( '[data-lookbook-preview]' );

	function reindex() {
		var rows = body.querySelectorAll( '[data-lookbook-row]' );
		rows.forEach( function ( row, index ) {
			var num = row.querySelector( '[data-lookbook-row-number]' );
			if ( num ) {
				num.textContent = String( index + 1 );
			}
			row.querySelectorAll( 'input' ).forEach( function ( input ) {
				var name = input.getAttribute( 'name' );
				if ( ! name ) {
					return;
				}
				input.setAttribute(
					'name',
					name.replace(
						/lookbook_hotspots\[\d+\]/,
						'lookbook_hotspots[' + index + ']'
					)
				);
			} );
		} );
		renderPins();
	}

	function renderPins() {
		if ( ! preview ) {
			return;
		}

		preview.querySelectorAll( '.lookbook-editor__pin' ).forEach( function (
			pin
		) {
			pin.remove();
		} );

		body.querySelectorAll( '[data-lookbook-row]' ).forEach( function (
			row,
			index
		) {
			var x = parseFloat( row.querySelector( '[data-lookbook-x]' ).value );
			var y = parseFloat( row.querySelector( '[data-lookbook-y]' ).value );

			if ( isNaN( x ) || isNaN( y ) ) {
				return;
			}

			var pin = document.createElement( 'span' );
			pin.className = 'lookbook-editor__pin';
			pin.style.left = Math.max( 0, Math.min( 100, x ) ) + '%';
			pin.style.top = Math.max( 0, Math.min( 100, y ) ) + '%';
			pin.textContent = String( index + 1 );
			preview.appendChild( pin );
		} );
	}

	function addRow() {
		var rows = body.querySelectorAll( '[data-lookbook-row]' );
		var template = rows[ rows.length - 1 ];

		if ( ! template ) {
			return;
		}

		var clone = template.cloneNode( true );
		clone.querySelectorAll( 'input' ).forEach( function ( input ) {
			if ( input.hasAttribute( 'data-lookbook-pid' ) ) {
				input.value = '';
			} else if ( input.hasAttribute( 'data-lookbook-x' ) ) {
				input.value = '50';
			} else if ( input.hasAttribute( 'data-lookbook-y' ) ) {
				input.value = '50';
			}
		} );
		body.appendChild( clone );
		reindex();
	}

	function removeRow( row ) {
		var rows = body.querySelectorAll( '[data-lookbook-row]' );

		if ( rows.length <= 1 ) {
			// Keep at least one (blank) row rather than leaving an empty table.
			row.querySelectorAll( 'input' ).forEach( function ( input ) {
				input.value = input.hasAttribute( 'data-lookbook-pid' )
					? ''
					: '50';
			} );
		} else {
			row.remove();
		}
		reindex();
	}

	editor.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '[data-lookbook-add]' ) ) {
			event.preventDefault();
			addRow();
			return;
		}

		var removeBtn = event.target.closest( '[data-lookbook-remove]' );
		if ( removeBtn ) {
			event.preventDefault();
			var confirmMsg = i18n.confirmRemove;
			if ( confirmMsg && ! window.confirm( confirmMsg ) ) {
				return;
			}
			removeRow( removeBtn.closest( '[data-lookbook-row]' ) );
		}
	} );

	editor.addEventListener( 'input', function ( event ) {
		if (
			event.target.hasAttribute( 'data-lookbook-x' ) ||
			event.target.hasAttribute( 'data-lookbook-y' )
		) {
			renderPins();
		}
	} );

	renderPins();
} )();
