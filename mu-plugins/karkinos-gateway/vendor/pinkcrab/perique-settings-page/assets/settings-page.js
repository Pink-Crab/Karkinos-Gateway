/**
 * Perique Settings Page — vanilla JS (no jQuery).
 *
 * Handles:
 * - Media Library field (open modal, select, clear)
 * - Repeater field (add, remove, drag-reorder via native drag events)
 * - Collapsible Sections (toggle body visibility)
 *
 * Picker initialisation lives below in a separate IIFE.
 */
(() => {
	'use strict';

	/* -------------------------------------------------------
	 * Media Library
	 * ------------------------------------------------------- */

	document.addEventListener( 'click', ( e ) => {
		const selectBtn = e.target.closest( '.pc-settings-media-select' );
		if ( selectBtn ) {
			e.preventDefault();
			const key     = selectBtn.getAttribute( 'data-key' );
			const input   = document.querySelector( `input[data-media-library-file-name="${ key }"]` );
			const preview = document.querySelector( `img[data-media-library-preview="${ key }"]` );
			const caption = document.getElementById( `${ key }_title` );

			const frame = wp.media( {
				title: 'Select Media',
				button: { text: 'Use this media' },
				multiple: false,
			} );

			frame.on( 'select', () => {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				if ( input ) input.value = attachment.id;
				if ( preview ) {
					preview.src = attachment.sizes?.thumbnail?.url ?? attachment.url;
				}
				if ( caption ) caption.textContent = attachment.title;
			} );

			frame.open();
			return;
		}

		const clearBtn = e.target.closest( '.pc-settings-media-clear' );
		if ( clearBtn ) {
			e.preventDefault();
			const key     = clearBtn.getAttribute( 'data-key' );
			const input   = document.querySelector( `input[data-media-library-file-name="${ key }"]` );
			const preview = document.querySelector( `img[data-media-library-preview="${ key }"]` );
			const caption = document.getElementById( `${ key }_title` );

			if ( input ) input.value = '';
			if ( preview ) preview.src = '';
			if ( caption ) caption.textContent = '';
		}
	} );

	/* -------------------------------------------------------
	 * Repeater
	 * ------------------------------------------------------- */

	/**
	 * Updates the sort order hidden input based on current row order.
	 *
	 * @param {string} repeaterKey
	 */
	const updateSortOrder = ( repeaterKey ) => {
		const container = document.querySelector(
			`[data-repeater-groups="${ repeaterKey }"]`
		);
		if ( ! container ) return;

		const indices = Array.from(
			container.querySelectorAll( '[data-repeater-row]' )
		).map( ( row ) => row.getAttribute( 'data-repeater-row' ) );

		const hidden = document.querySelector(
			`[data-repeater-sortorder="${ repeaterKey }"]`
		);
		if ( hidden ) hidden.value = indices.join( ',' );
	};

	/**
	 * Add a new row from the <template>.
	 */
	document.addEventListener( 'click', ( e ) => {
		const addBtn = e.target.closest( '[data-repeater-add]' );
		if ( ! addBtn ) return;
		e.preventDefault();

		const repeaterKey = addBtn.getAttribute( 'data-repeater-add' );
		const container   = document.querySelector(
			`[data-repeater-groups="${ repeaterKey }"]`
		);
		const template = document.querySelector(
			`[data-repeater-template="${ repeaterKey }"]`
		);
		if ( ! container || ! template ) return;

		// Work out the next index.
		let maxIndex = -1;
		container.querySelectorAll( '[data-repeater-row]' ).forEach( ( row ) => {
			const idx = parseInt( row.getAttribute( 'data-repeater-row' ), 10 );
			if ( ! isNaN( idx ) && idx > maxIndex ) maxIndex = idx;
		} );
		const newIndex = maxIndex + 1;

		// Clone template and replace placeholder index.
		const html = template.innerHTML.replace( /\{\{INDEX\}\}/g, String( newIndex ) );
		container.insertAdjacentHTML( 'beforeend', html );

		// Wire drag listeners on the new row.
		const newRow = container.querySelector(
			`[data-repeater-row="${ newIndex }"]`
		);
		if ( newRow ) initDragRow( newRow, repeaterKey );

		updateSortOrder( repeaterKey );
	} );

	/**
	 * Remove a row.
	 */
	document.addEventListener( 'click', ( e ) => {
		const removeBtn = e.target.closest( '[data-repeater-remove]' );
		if ( ! removeBtn ) return;
		e.preventDefault();

		const groupId    = removeBtn.getAttribute( 'data-repeater-remove' );
		const group      = document.getElementById( groupId );
		if ( ! group ) return;

		const repeater    = group.closest( '[data-repeater]' );
		const repeaterKey = repeater?.getAttribute( 'data-repeater' ) ?? '';

		group.remove();
		updateSortOrder( repeaterKey );
	} );

	/* -------------------------------------------------------
	 * Repeater Drag-and-Drop (native HTML5 drag events)
	 * ------------------------------------------------------- */

	/**
	 * Initialise drag listeners on a single repeater row.
	 *
	 * @param {HTMLElement} row
	 * @param {string}      repeaterKey
	 */
	const initDragRow = ( row, repeaterKey ) => {
		const handle = row.querySelector( '.pc-repeater__drag-handle' );
		if ( ! handle ) return;

		// The handle is the only draggable element — the row itself is
		// not, so input fields remain interactive.
		handle.setAttribute( 'draggable', 'true' );

		handle.addEventListener( 'dragstart', ( e ) => {
			// Stop the event from being eaten by children.
			e.stopPropagation();
			row.classList.add( 'pc-repeater__group--dragging' );
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'text/plain', row.id );
		} );

		row.addEventListener( 'dragend', () => {
			row.classList.remove( 'pc-repeater__group--dragging' );
			// Clear any leftover drop markers.
			row.closest( '[data-repeater-groups]' )
				?.querySelectorAll( '.pc-repeater__group--drag-over' )
				.forEach( ( el ) => el.classList.remove( 'pc-repeater__group--drag-over' ) );
			updateSortOrder( repeaterKey );
		} );

		row.addEventListener( 'dragover', ( e ) => {
			e.preventDefault();
			e.dataTransfer.dropEffect = 'move';
			row.classList.add( 'pc-repeater__group--drag-over' );
		} );

		row.addEventListener( 'dragleave', () => {
			row.classList.remove( 'pc-repeater__group--drag-over' );
		} );

		row.addEventListener( 'drop', ( e ) => {
			e.preventDefault();
			row.classList.remove( 'pc-repeater__group--drag-over' );

			const draggedId = e.dataTransfer.getData( 'text/plain' );
			const dragged   = document.getElementById( draggedId );
			if ( ! dragged || dragged === row ) return;

			const container = row.closest( '[data-repeater-groups]' );
			if ( ! container ) return;

			// Insert dragged before or after target based on position.
			const rect = row.getBoundingClientRect();
			const midY = rect.top + rect.height / 2;
			if ( e.clientY < midY ) {
				container.insertBefore( dragged, row );
			} else {
				container.insertBefore( dragged, row.nextSibling );
			}

			updateSortOrder( repeaterKey );
		} );
	};

	/**
	 * Initialise all existing repeater rows on page load.
	 */
	const initAllRepeaters = () => {
		document.querySelectorAll( '[data-repeater]' ).forEach( ( repeater ) => {
			const repeaterKey = repeater.getAttribute( 'data-repeater' );
			repeater
				.querySelectorAll( '[data-repeater-row]' )
				.forEach( ( row ) => initDragRow( row, repeaterKey ) );
		} );
	};

	/* -------------------------------------------------------
	 * Collapsible Sections
	 * ------------------------------------------------------- */

	document.addEventListener( 'click', ( e ) => {
		const header = e.target.closest( '.pc-form__section-header' );
		if ( ! header ) return;

		const section = header.closest( '.pc-form__section' );
		if ( ! section || section.getAttribute( 'data-collapsible' ) !== 'true' ) {
			return;
		}

		const body        = section.querySelector( '.pc-form__section-body' );
		const isCollapsed = section.getAttribute( 'data-collapsed' ) === 'true';

		if ( isCollapsed ) {
			if ( body ) body.style.display = '';
			section.setAttribute( 'data-collapsed', 'false' );
		} else {
			if ( body ) body.style.display = 'none';
			section.setAttribute( 'data-collapsed', 'true' );
		}
	} );

	/* -------------------------------------------------------
	 * Colour Picker — hex display
	 * ------------------------------------------------------- */

	const initColourPickers = () => {
		document.querySelectorAll( '.pc-form__element--color_input' ).forEach( ( wrapper ) => {
			const input = wrapper.querySelector( 'input[type="color"]' );
			if ( ! input ) return;

			// Create hex display if it doesn't exist.
			let hex = wrapper.querySelector( '.pc-color-hex' );
			if ( ! hex ) {
				hex = document.createElement( 'span' );
				hex.className = 'pc-color-hex';
				wrapper.appendChild( hex );
			}

			const update = () => {
				hex.textContent = input.value.toUpperCase();
			};

			update();
			input.addEventListener( 'input', update );
		} );
	};

	/* -------------------------------------------------------
	 * Init on DOM ready
	 * ------------------------------------------------------- */

	const initAll = () => {
		initAllRepeaters();
		initColourPickers();
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
})();

/* -------------------------------------------------------
 * Picker (Post / User) — Vanilla JS
 * ------------------------------------------------------- */
(() => {
	'use strict';

	const config = window.pcSettingsPage || {};

	/**
	 * Simple debounce.
	 */
	const debounce = (fn, delay) => {
		let timer;
		return (...args) => {
			clearTimeout(timer);
			timer = setTimeout(() => fn(...args), delay);
		};
	};

	/**
	 * POST to a REST endpoint.
	 */
	const restPost = (path, body) =>
		fetch(config.restUrl + path, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.restNonce || '',
			},
			body: JSON.stringify(body),
		}).then((res) => {
			if (!res.ok) throw new Error(`REST request failed: ${res.status}`);
			return res.json();
		});

	/**
	 * Initialise a single picker element.
	 */
	const initPicker = (el) => {
		const key = el.getAttribute('data-picker-key');
		const type = el.getAttribute('data-picker-type') || 'post';
		const isMultiple = el.getAttribute('data-picker-multiple') === 'true';
		const selectedContainer = el.querySelector('.pc-picker__selected');
		const searchInput = el.querySelector('.pc-picker__search');
		const searchWrap = el.querySelector('.pc-picker__search-wrap');

		let highlightIndex = -1;
		let dropdown = null;
		let selectedIds = [];

		const getSearchParams = (term) => {
			const params = { search: term, per_page: 10 };
			if (type === 'post') {
				params.post_type = el.getAttribute('data-picker-post-type') || 'post';
			} else if (type === 'user') {
				const role = el.getAttribute('data-picker-role') || '';
				if (role) params.role = role;
			}
			return params;
		};

		const getRestPath = (action) => {
			const base = type === 'user' ? 'users' : 'posts';
			return `${base}/${action}`;
		};

		const closeDropdown = () => {
			if (dropdown?.parentNode) {
				dropdown.parentNode.removeChild(dropdown);
			}
			dropdown = null;
			highlightIndex = -1;
		};

		const showDropdown = (items) => {
			closeDropdown();

			dropdown = document.createElement('div');
			dropdown.className = 'pc-picker__dropdown';

			const available = items.filter((item) => !selectedIds.includes(item.id));

			if (available.length === 0) {
				const msg = document.createElement('div');
				msg.className = 'pc-picker__no-results';
				msg.textContent = items.length === 0 ? 'No results found' : 'All results already selected';
				dropdown.appendChild(msg);
			} else {
				available.forEach((item) => {
					const option = document.createElement('div');
					option.className = 'pc-picker__option';
					option.dataset.id = item.id;
					option.dataset.text = item.text;
					option.textContent = item.text;
					option.addEventListener('click', () => selectItem(item.id, item.text));
					dropdown.appendChild(option);
				});
			}

			searchWrap.appendChild(dropdown);
		};

		const showLoading = () => {
			closeDropdown();
			dropdown = document.createElement('div');
			dropdown.className = 'pc-picker__dropdown';
			const loading = document.createElement('div');
			loading.className = 'pc-picker__loading';
			loading.textContent = 'Searching\u2026';
			dropdown.appendChild(loading);
			searchWrap.appendChild(dropdown);
		};

		const removeAllHiddenInputs = () => {
			el.querySelectorAll(`input[data-picker-input="${key}"]`)
				.forEach((input) => input.remove());
		};

		const addHiddenInput = (id) => {
			// Remove empty placeholder first.
			const empty = el.querySelector(`input[data-picker-input="${key}"][value=""]`);
			if (empty) empty.remove();

			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = isMultiple ? `${key}[]` : key;
			input.value = id;
			input.dataset.pickerInput = key;
			el.appendChild(input);
		};

		const addEmptyHiddenInput = () => {
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = isMultiple ? `${key}[]` : key;
			input.value = '';
			input.dataset.pickerInput = key;
			el.appendChild(input);
		};

		const addTag = (id, text) => {
			const tag = document.createElement('span');
			tag.className = 'pc-picker__tag';
			tag.dataset.pickerTagId = id;

			const label = document.createElement('span');
			label.textContent = text;

			const removeBtn = document.createElement('button');
			removeBtn.type = 'button';
			removeBtn.className = 'pc-picker__tag-remove';
			removeBtn.innerHTML = '&times;';
			removeBtn.addEventListener('click', () => removeItem(id));

			tag.appendChild(label);
			tag.appendChild(removeBtn);
			selectedContainer.appendChild(tag);
		};

		const selectItem = (id, text) => {
			const numId = parseInt(id, 10);

			if (!isMultiple) {
				selectedIds = [numId];
				selectedContainer.innerHTML = '';
				removeAllHiddenInputs();
				addTag(numId, text);
				addHiddenInput(numId);
			} else {
				if (selectedIds.includes(numId)) return;
				selectedIds.push(numId);
				addTag(numId, text);
				addHiddenInput(numId);
			}

			searchInput.value = '';
			closeDropdown();
		};

		const removeItem = (id) => {
			const numId = parseInt(id, 10);
			selectedIds = selectedIds.filter((i) => i !== numId);

			const tag = selectedContainer.querySelector(`[data-picker-tag-id="${numId}"]`);
			if (tag) tag.remove();

			const input = el.querySelector(`input[data-picker-input="${key}"][value="${numId}"]`);
			if (input) input.remove();

			if (selectedIds.length === 0) addEmptyHiddenInput();
		};

		const updateHighlight = (options) => {
			options.forEach((opt, idx) => {
				opt.classList.toggle('pc-picker__option--highlighted', idx === highlightIndex);
				if (idx === highlightIndex) opt.scrollIntoView({ block: 'nearest' });
			});
		};

		const handleKeydown = (e) => {
			if (!dropdown) return;

			const options = dropdown.querySelectorAll('.pc-picker__option');
			if (options.length === 0) return;

			switch (e.key) {
				case 'ArrowDown':
					e.preventDefault();
					highlightIndex = Math.min(highlightIndex + 1, options.length - 1);
					updateHighlight(options);
					break;
				case 'ArrowUp':
					e.preventDefault();
					highlightIndex = Math.max(highlightIndex - 1, 0);
					updateHighlight(options);
					break;
				case 'Enter':
					e.preventDefault();
					if (highlightIndex >= 0 && highlightIndex < options.length) {
						const opt = options[highlightIndex];
						selectItem(opt.dataset.id, opt.dataset.text);
					}
					break;
				case 'Escape':
					closeDropdown();
					break;
			}
		};

		const doSearch = debounce(() => {
			const term = searchInput.value.trim();
			if (term.length < 2) {
				closeDropdown();
				return;
			}

			showLoading();
			restPost(getRestPath('search'), getSearchParams(term))
				.then((results) => showDropdown(results))
				.catch(() => closeDropdown());
		}, 300);

		// Bind events.
		searchInput.addEventListener('input', doSearch);
		searchInput.addEventListener('keydown', handleKeydown);
		// Use mousedown (not click) to detect outside clicks. Click fires
		// after mousedown+mouseup — by then an option's click handler may
		// have removed the dropdown from the DOM, breaking el.contains().
		document.addEventListener('mousedown', (e) => {
			if (!el.contains(e.target)) closeDropdown();
		});

		// Resolve existing values on init.
		const initialValue = el.getAttribute('data-picker-value');
		if (initialValue) {
			try {
				const ids = JSON.parse(initialValue);
				if (Array.isArray(ids) && ids.length > 0) {
					const validIds = ids.filter((id) => id > 0);
					if (validIds.length > 0) {
						selectedIds = validIds;
						restPost(getRestPath('resolve'), { ids: validIds })
							.then((results) => results.forEach((item) => addTag(item.id, item.text)))
							.catch(() => { /* Tags just won't show labels. */ });
					}
				}
			} catch (_) {
				// Invalid JSON, ignore.
			}
		}
	};

	/**
	 * Initialise all pickers on the page.
	 */
	const initAllPickers = () => {
		document.querySelectorAll('[data-picker-key]').forEach(initPicker);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAllPickers);
	} else {
		initAllPickers();
	}
})();
