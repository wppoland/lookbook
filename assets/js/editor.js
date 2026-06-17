/**
 * Lookbook — hotspot editor (meta box) enhancements.
 *
 * Progressive, dependency-free. Adds/removes repeater rows and keeps the input
 * names contiguous after each change. With JS disabled the rows printed by PHP
 * still save normally — this only improves the authoring experience.
 */
( function () {
	'use strict';

	var editor = document.querySelector( '[data-lookbook-editor]' );

	if ( ! editor ) {
		return;
	}

	var i18n = ( window.lookbookEditor && window.lookbookEditor.i18n ) || {};
	var body = editor.querySelector( '[data-lookbook-rows-body]' );

	function reindex() {
		var rows = body.querySelectorAll( '[data-lookbook-row]' );
		rows.forEach( function ( row, index ) {
			var num = row.querySelector( '[data-lookbook-row-number]' );
			if ( num ) {
				num.textContent = String( index + 1 );
			}
			row.querySelectorAll( 'input' ).forEach( function ( input ) {
				var name = input.getAttribute( 'name' );
				if ( name ) {
					input.setAttribute(
						'name',
						name.replace(
							/lookbook_hotspots\[\d+\]/,
							'lookbook_hotspots[' + index + ']'
						)
					);
				}

				// Keep the per-row id and its matching <label for> unique so
				// each input has its own accessible name. Ids are minted by PHP
				// as lookbook_<field>_<rowIndex> (e.g. lookbook_x_0).
				var id = input.getAttribute( 'id' );
				if ( ! id ) {
					return;
				}
				var newId = id.replace( /_\d+$/, '_' + index );
				if ( newId === id ) {
					return;
				}
				var label = row.querySelector(
					'label[for="' + id + '"]'
				);
				input.setAttribute( 'id', newId );
				if ( label ) {
					label.setAttribute( 'for', newId );
				}
			} );
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
} )();
