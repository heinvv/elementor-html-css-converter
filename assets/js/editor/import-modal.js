console.log('[EHCC Import Modal] Script file loaded, initializing...');

(function($) {
	'use strict';

	console.log('[EHCC Import Modal] Script wrapper executed');
	console.log('[EHCC Import Modal] ehccImport data:', typeof ehccImport !== 'undefined' ? ehccImport : 'not defined');
	console.log('[EHCC Import Modal] jQuery available:', typeof $ !== 'undefined');

	const ehccImportModal = {
		modal: null,
		pollInterval: null,

		init() {
			console.log('[EHCC Import Modal] init() called');
			console.log('[EHCC Import Modal] elementorCommon defined:', typeof elementorCommon !== 'undefined');
			console.log('[EHCC Import Modal] dialogsManager defined:', typeof elementorCommon !== 'undefined' && typeof elementorCommon.dialogsManager !== 'undefined');

			if (typeof elementorCommon === 'undefined' || typeof elementorCommon.dialogsManager === 'undefined') {
				console.log('[EHCC Import Modal] Waiting for elementorCommon...');
				setTimeout(() => this.init(), 100);
				return;
			}

			console.log('[EHCC Import Modal] Creating modal...');
			this.createModal();
		},

		createModal() {
			console.log('[EHCC Import Modal] createModal() called');
			const modalOptions = {
				id: 'ehcc-import-modal',
				className: 'ehcc-import-modal',
				title: 'Import Website',
				closeButton: true,
				draggable: true,
				hide: {
					onOutsideClick: true,
					onEscKeyPress: true,
				},
			};

			try {
				this.modal = elementorCommon.dialogsManager.createWidget('lightbox', modalOptions);
				console.log('[EHCC Import Modal] Modal created successfully');
			} catch (error) {
				console.error('[EHCC Import Modal] Error creating modal:', error);
				return;
			}

			const $content = $('<div>', {
				class: 'ehcc-import-modal-content'
			});

			$content.html(this.getFormHTML());

			this.modal.getElements('message').html($content);

			this.attachEvents();
		},

		getFormHTML() {
			return `
				<form class="ehcc-import-form" id="ehcc-import-form">
					<div class="ehcc-form-field">
						<label for="ehcc-url">URL to Import <span class="required">*</span></label>
						<input type="url" id="ehcc-url" name="url" required placeholder="https://example.com/page" />
					</div>

					<div class="ehcc-form-field">
						<label for="ehcc-selectors">CSS Selectors <span class="required">*</span></label>
						<textarea id="ehcc-selectors" name="selectors" required rows="3" placeholder=".hero, .card, #main-content"></textarea>
						<small class="ehcc-field-description">Comma-separated CSS selectors</small>
					</div>

					<div class="ehcc-form-field">
						<label for="ehcc-timeout">Timeout (seconds)</label>
						<input type="number" id="ehcc-timeout" name="timeout" value="60" min="10" max="300" />
					</div>

					<div class="ehcc-form-actions">
						<button type="button" class="ehcc-button ehcc-button-cancel" data-action="cancel">Cancel</button>
						<button type="submit" class="ehcc-button ehcc-button-primary" data-action="submit">Start Import</button>
					</div>

					<div class="ehcc-status-message" id="ehcc-status-message" style="display: none;"></div>
				</form>
			`;
		},

		attachEvents() {
			const self = this;

			this.modal.getElements('widget').on('submit', '#ehcc-import-form', function(e) {
				e.preventDefault();
				self.handleSubmit();
			});

			this.modal.getElements('widget').on('click', '.ehcc-button-cancel', function() {
				self.close();
			});

			this.modal.getElements('widget').on('click', '.dialog-close-button', function() {
				self.close();
			});
		},

		handleSubmit() {
			const form = document.getElementById('ehcc-import-form');
			if (!form) {
				return;
			}

			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}

			const urlParams = new URLSearchParams(window.location.search);
			const postId = urlParams.get('post') || (window.elementor?.config?.initial_document?.id) || null;

			const formData = {
				url: document.getElementById('ehcc-url').value.trim(),
				selectors: document.getElementById('ehcc-selectors').value.trim(),
				timeout: document.getElementById('ehcc-timeout').value || '60',
				elementor_base_url: window.location.origin,
				wordpress_website_url: window.location.origin,
				post_id: postId,
			};

			this.showLoading();
			this.submitRequest(formData);
		},

		submitRequest(formData) {
			const self = this;
			const apiUrl = ehccImport.apiUrl + 'trigger-import';

			$.ajax({
				url: apiUrl,
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify(formData),
				success: function(response) {
					if (response.success && response.job_id) {
						self.showSuccess('Import started. Waiting for results...');
						self.startPolling(response.job_id);
					} else {
						self.showError(response.message || 'Failed to start import');
					}
				},
				error: function(xhr) {
					let errorMessage = 'Failed to start import';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}
					self.showError(errorMessage);
				}
			});
		},

		startPolling(jobId) {
			const self = this;
			let attempts = 0;
			const maxAttempts = 60;
			const pollInterval = 2000;

			this.pollInterval = setInterval(function() {
				attempts++;

				if (attempts > maxAttempts) {
					clearInterval(self.pollInterval);
					self.showError('Timeout waiting for results');
					return;
				}

				self.checkResults(jobId);
			}, pollInterval);
		},

		checkResults(jobId) {
			const self = this;
			const apiUrl = ehccImport.apiUrl + 'import-results/' + jobId;

			$.ajax({
				url: apiUrl,
				method: 'GET',
				success: function(response) {
					if (response.success && response.data) {
						clearInterval(self.pollInterval);
						self.handleResults(response.data);
					}
				},
				error: function(xhr) {
					if (xhr.status === 404) {
						return;
					}

					clearInterval(self.pollInterval);
					self.showError('Failed to fetch results');
				}
			});
		},

		handleResults(data) {
			if (data.status === 'success' && data.results) {
				this.showSuccess('Import completed successfully!');
				setTimeout(() => {
					this.close();
					if (typeof elementor !== 'undefined') {
						elementor.notifications.showToast({
							message: 'Website imported successfully. Check results.',
							type: 'success'
						});
					}
				}, 2000);
			} else if (data.status === 'error') {
				this.showError(data.error || 'Import failed');
			}
		},

		showLoading() {
			this.hideStatus();
			const $submitButton = this.modal.getElements('widget').find('.ehcc-button-primary');
			$submitButton.prop('disabled', true).text('Starting...');
		},

		showSuccess(message) {
			this.showStatus(message, 'success');
			const $submitButton = this.modal.getElements('widget').find('.ehcc-button-primary');
			$submitButton.prop('disabled', false).text('Start Import');
		},

		showError(message) {
			this.showStatus(message, 'error');
			const $submitButton = this.modal.getElements('widget').find('.ehcc-button-primary');
			$submitButton.prop('disabled', false).text('Start Import');
		},

		showStatus(message, type) {
			const $statusMessage = this.modal.getElements('widget').find('#ehcc-status-message');
			$statusMessage
				.removeClass('ehcc-status-success ehcc-status-error')
				.addClass('ehcc-status-' + type)
				.text(message)
				.show();
		},

		hideStatus() {
			this.modal.getElements('widget').find('#ehcc-status-message').hide();
		},

		open() {
			if (!this.modal) {
				this.init();
				setTimeout(() => this.open(), 200);
				return;
			}

			this.resetForm();
			this.modal.show();
		},

		close() {
			if (this.pollInterval) {
				clearInterval(this.pollInterval);
				this.pollInterval = null;
			}

			if (this.modal) {
				this.modal.hide();
				this.resetForm();
			}
		},

		resetForm() {
			if (this.modal) {
				const $form = this.modal.getElements('widget').find('#ehcc-import-form');
				if ($form.length) {
					$form[0].reset();
					this.hideStatus();
				}
			}
		}
	};

	if (typeof elementorCommon !== 'undefined' && typeof elementorCommon.dialogsManager !== 'undefined') {
		console.log('[EHCC Import Modal] elementorCommon available, initializing immediately');
		ehccImportModal.init();
	} else {
		console.log('[EHCC Import Modal] Waiting for elementor:init event');
		$(document).on('elementor:init', function() {
			console.log('[EHCC Import Modal] elementor:init event fired, initializing');
			ehccImportModal.init();
		});
	}

	window.ehccImportModal = ehccImportModal;
	console.log('[EHCC Import Modal] Modal object attached to window');
})(jQuery);
