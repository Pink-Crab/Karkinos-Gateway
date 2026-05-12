/**
 * Dev Asset meta box — wp.media picker glue.
 *
 * On select:
 *   - image → render full-size <img> in the preview slot, title as plain text.
 *   - non-image → leave preview empty, render the title as a clickable link
 *     (with the mime-icon next to it) so the file can be opened in a new tab.
 *
 * Requires `media-editor` (loaded via wp_enqueue_media()).
 */
( function () {
	document.addEventListener( 'click', function ( event ) {
		const selectBtn = event.target.closest( '.kg-da-media-select' );
		if ( selectBtn ) {
			event.preventDefault();
			openPicker( selectBtn );
			return;
		}

		const clearBtn = event.target.closest( '.kg-da-media-clear' );
		if ( clearBtn ) {
			event.preventDefault();
			clearPicker( clearBtn.dataset.key );
		}
	} );

	function refs( key ) {
		return {
			input:   document.querySelector( `input[data-kg-da-attachment="${ key }"]` ),
			preview: document.querySelector( `[data-kg-da-preview="${ key }"]` ),
			title:   document.querySelector( `[data-kg-da-title="${ key }"]` ),
		};
	}

	function openPicker( btn ) {
		const key = btn.dataset.key;
		const { input, preview, title } = refs( key );

		const frame = wp.media( {
			title:    btn.dataset.frameTitle || 'Select File',
			button:   { text: btn.dataset.buttonText || 'Use this file' },
			multiple: false,
		} );

		frame.on( 'select', function () {
			const att = frame.state().get( 'selection' ).first().toJSON();
			if ( input )   input.value = att.id;
			if ( preview ) renderPreview( preview, att );
			if ( title )   renderTitle( title, att );
		} );

		frame.open();
	}

	function clearPicker( key ) {
		const { input, preview, title } = refs( key );
		if ( input )   input.value       = '';
		if ( preview ) preview.innerHTML = '';
		if ( title )   title.textContent = '';
	}

	function renderPreview( preview, att ) {
		preview.innerHTML = '';

		// Only image types get a visual preview (full-size).
		if ( att.type !== 'image' ) {
			return;
		}

		const img = document.createElement( 'img' );
		img.src = att.url;
		img.alt = att.title || '';
		preview.appendChild( img );
	}

	function renderTitle( title, att ) {
		title.innerHTML = '';
		const label = att.title || `#${ att.id }`;

		// Image → plain text label; the <img> is the visual.
		if ( att.type === 'image' ) {
			title.textContent = label;
			return;
		}

		// Non-image → clickable link, with the mime icon for visual cue.
		const link = document.createElement( 'a' );
		link.href   = att.url;
		link.target = '_blank';
		link.rel    = 'noopener';

		if ( att.icon ) {
			const icon = document.createElement( 'img' );
			icon.src = att.icon;
			icon.alt = '';
			link.appendChild( icon );
		}

		const span = document.createElement( 'span' );
		span.textContent = label;
		link.appendChild( span );

		title.appendChild( link );
	}
} )();
