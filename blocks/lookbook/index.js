/**
 * Lookbook block — editor registration.
 *
 * No build step: this uses the global wp.* packages (already enqueued as the
 * block's editorScript dependency-free script). The block is dynamic — the
 * server renders the chosen lookbook — so the editor only needs to collect the
 * lookbook ID. A ServerSideRender keeps the editor preview honest.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.blockEditor ) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n && wp.i18n.__ ? wp.i18n.__ : function ( s ) {
		return s;
	};
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var Placeholder = wp.components.Placeholder;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'wppoland/lookbook', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var idControl = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Lookbook', 'lookbook' ), initialOpen: true },
					el( TextControl, {
						label: __( 'Lookbook ID', 'lookbook' ),
						type: 'number',
						value: attributes.lookbookId
							? String( attributes.lookbookId )
							: '',
						help: __(
							'Enter the ID of the lookbook to display. Find it in the Lookbooks list.',
							'lookbook'
						),
						onChange: function ( value ) {
							setAttributes( {
								lookbookId: parseInt( value, 10 ) || 0,
							} );
						},
					} )
				)
			);

			var preview;

			if ( attributes.lookbookId && ServerSideRender ) {
				preview = el( ServerSideRender, {
					block: 'wppoland/lookbook',
					attributes: attributes,
				} );
			} else {
				preview = el(
					Placeholder,
					{
						icon: 'format-image',
						label: __( 'Lookbook', 'lookbook' ),
						instructions: __(
							'Enter a lookbook ID in the block settings to preview it.',
							'lookbook'
						),
					}
				);
			}

			return el( 'div', blockProps, idControl, preview );
		},
		save: function () {
			// Dynamic block — rendered by PHP.
			return null;
		},
	} );
} )( window.wp );
