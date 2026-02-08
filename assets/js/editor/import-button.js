console.log('[EHCC Import Button] Script file loaded, initializing...');

(function($) {
	'use strict';

	console.log('[EHCC Import Button] Script wrapper executed');
	console.log('[EHCC Import Button] jQuery available:', typeof $ !== 'undefined');
	console.log('[EHCC Import Button] jQuery version:', typeof $ !== 'undefined' && $.fn ? $.fn.jquery : 'N/A');
	console.log('[EHCC Import Button] Document ready state:', document.readyState);
	console.log('[EHCC Import Button] Window location:', window.location.href);

	if (typeof $ === 'undefined') {
		console.error('[EHCC Import Button] jQuery is not available!');
		if (typeof jQuery !== 'undefined') {
			console.log('[EHCC Import Button] jQuery found as global, using it');
			$ = jQuery;
		} else {
			console.error('[EHCC Import Button] jQuery not found, script cannot continue');
			return;
		}
	}

	function initImportButton() {
		console.log('[EHCC Import Button] initImportButton called');
		
		const $addSectionArea = $('.elementor-add-new-section');
		console.log('[EHCC Import Button] Found add section area:', $addSectionArea.length);
		console.log('[EHCC Import Button] All add section areas:', $('.elementor-add-section').length);
		console.log('[EHCC Import Button] Add section inner:', $('.elementor-add-section-inner').length);

		if (!$addSectionArea.length) {
			const $addSectionInner = $('.elementor-add-section-inner');
			if ($addSectionInner.length) {
				console.log('[EHCC Import Button] Found elementor-add-section-inner instead');
				const $view = $addSectionInner.find('.e-view.elementor-add-new-section');
				if ($view.length) {
					console.log('[EHCC Import Button] Found view element, trying to inject there');
					injectButtonIntoView($view);
					return;
				}
			}
			console.log('[EHCC Import Button] Add section area not found, will retry via observer');
			return;
		}

		injectButtonIntoContainer($addSectionArea);
	}

	function injectButtonIntoContainer($container) {
		const $templateButton = $container.find('.elementor-add-template-button');
		const $existingImportButton = $container.find('.ehcc-import-button');
		console.log('[EHCC Import Button] Template button found:', $templateButton.length);
		console.log('[EHCC Import Button] Existing import button found:', $existingImportButton.length);

		if ($existingImportButton.length) {
			console.log('[EHCC Import Button] Button already exists, skipping');
			return;
		}

		const $importButton = $('<button>', {
			type: 'button',
			class: 'elementor-add-section-area-button ehcc-import-button',
			'data-tooltip': 'Import Website',
			'aria-label': 'Import Website',
			html: '<i class="eicon-globe" aria-hidden="true"></i>'
		});

		console.log('[EHCC Import Button] Created button element');

		if ($templateButton.length) {
			$templateButton.after($importButton);
			console.log('[EHCC Import Button] Inserted after template button');
		} else {
			const $addButton = $container.find('.elementor-add-section-button');
			console.log('[EHCC Import Button] Add button found:', $addButton.length);
			if ($addButton.length) {
				$addButton.after($importButton);
				console.log('[EHCC Import Button] Inserted after add button');
			} else {
				const $aiButton = $container.find('.e-ai-layout-button');
				if ($aiButton.length) {
					$aiButton.after($importButton);
					console.log('[EHCC Import Button] Inserted after AI button');
				} else {
					$container.prepend($importButton);
					console.log('[EHCC Import Button] Prepended to container');
				}
			}
		}

		attachButtonClickHandler($importButton);
		console.log('[EHCC Import Button] Button initialization complete');
	}

	function injectButtonIntoView($view) {
		const $existingImportButton = $view.find('.ehcc-import-button');
		if ($existingImportButton.length) {
			console.log('[EHCC Import Button] Button already exists in view');
			return;
		}

		const $templateButton = $view.find('.elementor-add-template-button');
		const $importButton = $('<button>', {
			type: 'button',
			class: 'elementor-add-section-area-button ehcc-import-button',
			'data-tooltip': 'Import Website',
			'aria-label': 'Import Website',
			html: '<i class="eicon-globe" aria-hidden="true"></i>'
		});

		if ($templateButton.length) {
			$templateButton.after($importButton);
		} else {
			const $addButton = $view.find('.elementor-add-section-button');
			if ($addButton.length) {
				$addButton.after($importButton);
			} else {
				$view.prepend($importButton);
			}
		}

		attachButtonClickHandler($importButton);
	}

	function attachButtonClickHandler($button) {
		$button.on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			console.log('[EHCC Import Button] Button clicked');

			const modalReact = window.parent.ehccImportModalReact || top.ehccImportModalReact || window.ehccImportModalReact;
			const modal = window.parent.ehccImportModal || top.ehccImportModal || window.ehccImportModal;

			if (modalReact) {
				console.log('[EHCC Import Button] Opening React modal');
				modalReact.open();
			} else if (modal) {
				console.log('[EHCC Import Button] Opening jQuery modal');
				modal.open();
			} else {
				console.error('[EHCC Import Button] No modal found');
				console.log('[EHCC Import Button] window.parent.ehccImportModalReact:', typeof window.parent !== 'undefined' ? typeof window.parent.ehccImportModalReact : 'N/A');
				console.log('[EHCC Import Button] window.ehccImportModalReact:', typeof window.ehccImportModalReact);
			}
		});
	}

	function waitForElementor() {
		console.log('[EHCC Import Button] Checking for Elementor...');
		console.log('[EHCC Import Button] elementor defined:', typeof elementor !== 'undefined');
		console.log('[EHCC Import Button] jQuery defined:', typeof jQuery !== 'undefined');
		console.log('[EHCC Import Button] window.elementorCommon defined:', typeof window.elementorCommon !== 'undefined');

		if (typeof jQuery === 'undefined') {
			console.log('[EHCC Import Button] jQuery not found, retrying...');
			setTimeout(waitForElementor, 100);
			return;
		}

		console.log('[EHCC Import Button] jQuery found, setting up event listeners...');

	function getPreviewDocument() {
		if (typeof elementor === 'undefined' || !elementor.$preview || !elementor.$preview.length) {
			return null;
		}
		const iframe = elementor.$preview[0];
		if (!iframe || !iframe.contentWindow || !iframe.contentDocument) {
			return null;
		}
		return iframe.contentDocument;
	}

	function tryInitInPreview() {
		const previewDoc = getPreviewDocument();
		if (!previewDoc) {
			console.log('[EHCC Import Button] Preview iframe not available yet');
			return false;
		}

		const $previewDoc = $(previewDoc);
		const $addSectionArea = $previewDoc.find('.elementor-add-new-section');
		console.log('[EHCC Import Button] Preview iframe found');
		console.log('[EHCC Import Button] Add section area in preview:', $addSectionArea.length);

		if ($addSectionArea.length) {
			injectButtonIntoContainer($addSectionArea);
			return true;
		}

		const $addSectionInner = $previewDoc.find('.elementor-add-section-inner');
		if ($addSectionInner.length) {
			console.log('[EHCC Import Button] Found elementor-add-section-inner in preview');
			const $view = $addSectionInner.find('.e-view.elementor-add-new-section');
			if ($view.length) {
				injectButtonIntoView($view);
				return true;
			}
		}

		return false;
	}

	function tryInit() {
		console.log('[EHCC Import Button] Attempting to initialize button...');
		
		if (tryInitInPreview()) {
			console.log('[EHCC Import Button] Button injected in preview iframe');
			return;
		}

		initImportButton();
	}

	$(document).ready(function() {
		console.log('[EHCC Import Button] Document ready');

		setTimeout(tryInit, 2000);

		$(window).on('elementor:loaded', function() {
			console.log('[EHCC Import Button] elementor:loaded event fired');
			setTimeout(tryInit, 1000);
		});

		$(window).on('elementor:init', function() {
			console.log('[EHCC Import Button] elementor:init event fired');
			setTimeout(tryInit, 1000);
		});

		$(window).on('elementor/init', function() {
			console.log('[EHCC Import Button] elementor/init event fired');
			setTimeout(tryInit, 1000);
		});

		if (typeof elementor !== 'undefined') {
			console.log('[EHCC Import Button] Elementor already available');
			elementor.on('preview:loaded', function() {
				console.log('[EHCC Import Button] Preview loaded event fired');
				setTimeout(tryInit, 500);
			});
			setTimeout(tryInit, 2000);
		}

		if (typeof MutationObserver !== 'undefined') {
			const observer = new MutationObserver(function(mutations) {
				if (tryInitInPreview()) {
					console.log('[EHCC Import Button] MutationObserver detected add section area in preview');
				}
			});

			observer.observe(document.body, {
				childList: true,
				subtree: true
			});

			console.log('[EHCC Import Button] MutationObserver set up');
		}
	});
	}

	waitForElementor();
})(jQuery);
