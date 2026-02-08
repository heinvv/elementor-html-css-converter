(function($) {
	'use strict';

	if (typeof $ === 'undefined') {
		if (typeof jQuery !== 'undefined') {
			$ = jQuery;
		} else {
			return;
		}
	}

	function initImportButton() {
		const $addSectionArea = $('.elementor-add-new-section');

		if (!$addSectionArea.length) {
			const $addSectionInner = $('.elementor-add-section-inner');
			if ($addSectionInner.length) {
				const $view = $addSectionInner.find('.e-view.elementor-add-new-section');
				if ($view.length) {
					injectButtonIntoView($view);
					return;
				}
			}
			return;
		}

		injectButtonIntoContainer($addSectionArea);
	}

	function injectButtonIntoContainer($container) {
		const $templateButton = $container.find('.elementor-add-template-button');
		const $existingImportButton = $container.find('.ehcc-import-button');

		if ($existingImportButton.length) {
			return;
		}

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
			const $addButton = $container.find('.elementor-add-section-button');
			if ($addButton.length) {
				$addButton.after($importButton);
			} else {
				const $aiButton = $container.find('.e-ai-layout-button');
				if ($aiButton.length) {
					$aiButton.after($importButton);
				} else {
					$container.prepend($importButton);
				}
			}
		}

		attachButtonClickHandler($importButton);
	}

	function injectButtonIntoView($view) {
		const $existingImportButton = $view.find('.ehcc-import-button');
		if ($existingImportButton.length) {
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

			const modalReact = window.parent.ehccImportModalReact || top.ehccImportModalReact || window.ehccImportModalReact;

			if (modalReact) {
				modalReact.open();
			}
		});
	}

	function waitForElementor() {
		if (typeof jQuery === 'undefined') {
			setTimeout(waitForElementor, 100);
			return;
		}

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
			return false;
		}

		const $previewDoc = $(previewDoc);
		const $addSectionArea = $previewDoc.find('.elementor-add-new-section');

		if ($addSectionArea.length) {
			injectButtonIntoContainer($addSectionArea);
			return true;
		}

		const $addSectionInner = $previewDoc.find('.elementor-add-section-inner');
		if ($addSectionInner.length) {
			const $view = $addSectionInner.find('.e-view.elementor-add-new-section');
			if ($view.length) {
				injectButtonIntoView($view);
				return true;
			}
		}

		return false;
	}

	function tryInit() {
		if (tryInitInPreview()) {
			return;
		}

		initImportButton();
	}

	$(document).ready(function() {
		setTimeout(tryInit, 2000);

		$(window).on('elementor:loaded', function() {
			setTimeout(tryInit, 1000);
		});

		$(window).on('elementor:init', function() {
			setTimeout(tryInit, 1000);
		});

		$(window).on('elementor/init', function() {
			setTimeout(tryInit, 1000);
		});

		if (typeof elementor !== 'undefined') {
			elementor.on('preview:loaded', function() {
				setTimeout(tryInit, 500);
			});
			setTimeout(tryInit, 2000);
		}

		if (typeof MutationObserver !== 'undefined') {
			const observer = new MutationObserver(function(mutations) {
				tryInitInPreview();
			});

			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		}
	});
	}

	waitForElementor();
})(jQuery);
