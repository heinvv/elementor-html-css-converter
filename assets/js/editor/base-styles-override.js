(function() {
	'use strict';

	jQuery(window).on('elementor:init', function() {
		const originalGetAtomicWidgetBaseStyles = elementor.helpers.getAtomicWidgetBaseStyles;

		elementor.helpers.getAtomicWidgetBaseStyles = function(model) {
			const editorSettings = model?.get?.('editor_settings') || {};
			const isConverterWidget =
				true === editorSettings.disable_base_styles ||
				true === editorSettings.css_converter_widget ||
				'0.0' === model?.get?.('version') ||
				hasConverterClassPattern(model);

			if (isConverterWidget) {
				return {};
			}

			return originalGetAtomicWidgetBaseStyles.call(this, model);
		};

		function hasConverterClassPattern(model) {
			const settings = model?.get?.('settings');
			if (!settings || !Array.isArray(settings.classes)) {
				return false;
			}
			const converterClassPattern = /^e-[a-f0-9]{7,8}-[a-f0-9]{7}$/;
			return settings.classes.some(function(cls) {
				return typeof cls === 'string' && converterClassPattern.test(cls);
			});
		}

		elementor.on('document:loaded', function() {
			clearHtmlCacheForConverterWidgets();
			removeBaseClassesFromDOM();
		});

		const $e = window.$e;
		if ($e && $e.commands && $e.commands.on) {
			$e.commands.on('run:after', function(component, command) {
				if (command === 'document/elements/import') {
					setTimeout(function() {
						clearHtmlCacheForConverterWidgets();
						removeBaseClassesFromDOM();
					}, 300);
				}
			});
		}

		function clearHtmlCacheForConverterWidgets() {
			try {
				const previewView = elementor.getPreviewView();
				if (!previewView || !previewView.collection) {
					return;
				}

				const elements = previewView.collection.models;
				let pendingRenders = 0;

				elements.forEach(function(element) {
					const result = processElementRecursively(element);
					pendingRenders += result.pending;
				});

				if (pendingRenders > 0) {
					waitForAllRendersComplete(pendingRenders);
				}
			} catch (error) {}
		}

		function processElementRecursively(model) {
			let processedCount = 0;
			let pendingRenders = 0;

			const result = clearIfConverterWidget(model);
			if (result.processed) {
				processedCount++;
			}
			if (result.pending) {
				pendingRenders++;
			}

			const children = model.get('elements');
			if (children && children.models) {
				children.models.forEach(function(child) {
					const childResult = processElementRecursively(child);
					processedCount += childResult.processed;
					pendingRenders += childResult.pending;
				});
			}

			return { processed: processedCount, pending: pendingRenders };
		}

		function clearIfConverterWidget(model) {
			if (model.get('elType') !== 'widget') {
				return { processed: false, pending: false };
			}

			const editorSettings = model.get('editor_settings') || {};
			const version = model.get('version');

			const isConverterWidget =
				true === editorSettings.disable_base_styles ||
				true === editorSettings.css_converter_widget ||
				'0.0' === version ||
				hasConverterClassPattern(model);

			if (!isConverterWidget) {
				return { processed: false, pending: false };
			}

			const hadCache = !!model.getHtmlCache();
			model.setHtmlCache(null);

			if (model.remoteRender && hadCache) {
				model.once('remote:render', function() {
					window.cssConverterRenderCompleted && window.cssConverterRenderCompleted();
				});

				if (!model.isRemoteRequestActive()) {
					model.renderRemoteServer();
					return { processed: true, pending: true };
				}
				return { processed: true, pending: false };
			}

			return { processed: true, pending: false };
		}

		function waitForAllRendersComplete(pendingCount) {
			let completedCount = 0;

			window.cssConverterRenderCompleted = function() {
				completedCount++;

				if (completedCount >= pendingCount) {
					delete window.cssConverterRenderCompleted;

					setTimeout(function() {
						elementor.reloadPreview();
					}, 100);
				}
			};

			setTimeout(function() {
				if (window.cssConverterRenderCompleted) {
					delete window.cssConverterRenderCompleted;
					elementor.reloadPreview();
				}
			}, 5000);
		}

		function removeBaseClassesFromDOM() {
			let attempts = 0;
			const maxAttempts = 5;

			function attemptRemoval() {
				attempts++;

				let totalRemoved = 0;

				const iframe = document.querySelector('#elementor-preview-iframe');
				if (iframe && iframe.contentDocument) {
					totalRemoved += removeBaseClassesFromDocument(iframe.contentDocument);
				}

				totalRemoved += removeBaseClassesFromDocument(document);

				if (totalRemoved > 0 || attempts >= maxAttempts) {
					return;
				}

				setTimeout(attemptRemoval, 500);
			}

			attemptRemoval();

			const iframe = document.querySelector('#elementor-preview-iframe');
			if (iframe) {
				iframe.addEventListener('load', function() {
					setTimeout(function() {
						removeBaseClassesFromDocument(iframe.contentDocument);
					}, 100);
				});
			}
		}

		function removeBaseClassesFromDocument(doc) {
			try {
				const elementsWithBaseClasses = doc.querySelectorAll('[class*="-base"]');
				let removedCount = 0;

				elementsWithBaseClasses.forEach(function(element) {
					const classList = element.classList;
					const classesToRemove = [];

					classList.forEach(function(className) {
						if (/^e-[\w-]+-base$/.test(className)) {
							classesToRemove.push(className);
						}
					});

					classesToRemove.forEach(function(className) {
						element.classList.remove(className);
						removedCount++;
					});
				});

				return removedCount;
			} catch (error) {
				return 0;
			}
		}
	});
}());

