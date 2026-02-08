(function() {
	'use strict';

	console.log('[EHCC Import Modal React] Script loaded');

	function waitForReact() {
		const ui = window.elementorV2?.ui;
		if (!ui) {
			setTimeout(waitForReact, 100);
			return;
		}

		const React = window.React || window.elementorV2?.react;
		const ReactDOM = window.ReactDOM || window.elementorV2?.reactDOM;

		if (!React || !ReactDOM) {
			console.error('[EHCC Import Modal React] React or ReactDOM not available');
			console.log('[EHCC Import Modal React] Available:', {
				React: typeof React,
				ReactDOM: typeof ReactDOM,
				elementorV2: typeof window.elementorV2,
				ui: typeof ui
			});
			setTimeout(waitForReact, 100);
			return;
		}

		console.log('[EHCC Import Modal React] React and UI found, initializing...');
		initModal(React, ReactDOM, ui);
	}

	function initModal(React, ReactDOM, ui) {
		const { useState, useEffect } = React;
		const { createRoot } = ReactDOM.client || {};
		const { render: ReactDOMRender } = ReactDOM;

		const {
			Dialog,
			DialogTitle,
			DialogContent,
			DialogActions,
			DialogContentText,
			Button,
			TextField,
			Stack,
			Box,
			CircularProgress,
			Alert,
			IconButton,
		} = ui;

		const ImportModal = ({ isOpen, onClose, apiUrl, postId }) => {
			const [url, setUrl] = useState('');
			const [selectors, setSelectors] = useState('');
			const [timeout, setTimeout] = useState('60');
			const [isLoading, setIsLoading] = useState(false);
			const [statusMessage, setStatusMessage] = useState(null);
			const [statusType, setStatusType] = useState(null);
			const [pollInterval, setPollInterval] = useState(null);

			useEffect(() => {
				if (!isOpen) {
					if (pollInterval) {
						clearInterval(pollInterval);
						setPollInterval(null);
					}
					setStatusMessage(null);
					setStatusType(null);
					setIsLoading(false);
				}
			}, [isOpen, pollInterval]);

			const handleSubmit = async (e) => {
				e.preventDefault();

				if (!url || !selectors) {
					setStatusMessage('Please fill in all required fields');
					setStatusType('error');
					return;
				}

				setIsLoading(true);
				setStatusMessage(null);
				setStatusType(null);

				try {
					const response = await fetch(apiUrl + 'trigger-import', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify({
							url: url.trim(),
							selectors: selectors.trim(),
							timeout: timeout || '60',
							elementor_base_url: window.location.origin,
							wordpress_website_url: window.location.origin,
							post_id: postId || null,
						}),
					});

					const data = await response.json();

					if (data.success && data.job_id) {
						setStatusMessage('Import started. Waiting for results...');
						setStatusType('info');
						startPolling(data.job_id);
					} else {
						setStatusMessage(data.message || 'Failed to start import');
						setStatusType('error');
						setIsLoading(false);
					}
				} catch (error) {
					setStatusMessage('Failed to start import: ' + error.message);
					setStatusType('error');
					setIsLoading(false);
				}
			};

			const startPolling = (jobId) => {
				let attempts = 0;
				const maxAttempts = 60;
				const interval = setInterval(async () => {
					attempts++;

					if (attempts > maxAttempts) {
						clearInterval(interval);
						setStatusMessage('Timeout waiting for results');
						setStatusType('error');
						setIsLoading(false);
						return;
					}

					try {
						const response = await fetch(apiUrl + 'import-results/' + jobId);
						const data = await response.json();

						if (data.success && data.data) {
							if (data.data.status === 'success') {
								clearInterval(interval);
								setStatusMessage('Import completed successfully!');
								setStatusType('success');
								setIsLoading(false);
								setTimeout(() => {
									onClose();
									if (window.elementor?.notifications) {
										window.elementor.notifications.showToast({
											message: 'Website imported successfully. Check results.',
											type: 'success',
										});
									}
								}, 2000);
							} else if (data.data.status === 'error') {
								clearInterval(interval);
								setStatusMessage(data.data.error || 'Import failed');
								setStatusType('error');
								setIsLoading(false);
							}
						}
					} catch (error) {
						if (error.status !== 404) {
							clearInterval(interval);
							setStatusMessage('Failed to fetch results');
							setStatusType('error');
							setIsLoading(false);
						}
					}
				}, 2000);

				setPollInterval(interval);
			};

			const handleClose = () => {
				if (pollInterval) {
					clearInterval(pollInterval);
					setPollInterval(null);
				}
				setUrl('');
				setSelectors('');
				setTimeout('60');
				setStatusMessage(null);
				setStatusType(null);
				setIsLoading(false);
				onClose();
			};

			if (!isOpen) {
				return null;
			}

			const headerElement = React.createElement(Box, {
				key: 'header',
				component: 'div',
				className: 'elementor-templates-modal__header',
				sx: {
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'space-between',
					height: '50px',
					borderBottom: 'var(--e-a-border, 1px solid #d5dade)',
				},
			}, [
				React.createElement(Box, {
					key: 'logo-area',
					component: 'div',
					className: 'elementor-templates-modal__header__logo-area',
					sx: {
						textAlign: 'start',
						paddingInlineStart: '15px',
					},
				}, React.createElement(Box, {
					component: 'div',
					className: 'elementor-templates-modal__header__logo',
					sx: {
						display: 'flex',
						alignItems: 'center',
						lineHeight: 1,
						textTransform: 'uppercase',
						fontWeight: 'bold',
					},
				}, [
					React.createElement(Box, {
						key: 'icon-wrapper',
						component: 'span',
						className: 'elementor-templates-modal__header__logo__icon-wrapper e-logo-wrapper',
						sx: {
							marginInlineEnd: '10px',
							fontSize: '12px',
						},
					}, React.createElement('i', {
						className: 'eicon-globe',
					})),
					React.createElement(Box, {
						key: 'title',
						component: 'span',
						className: 'elementor-templates-modal__header__logo__title',
						sx: {
							color: 'var(--e-a-color-txt-active, #515962)',
							paddingBlockStart: '2px',
						},
					}, 'Import Website'),
				])),
				React.createElement(Box, {
					key: 'items-area',
					component: 'div',
					className: 'elementor-templates-modal__header__items-area',
					sx: {
						display: 'flex',
						flexDirection: 'row-reverse',
					},
				}, React.createElement(Box, {
					component: 'div',
					className: 'elementor-templates-modal__header__close elementor-templates-modal__header__close--normal elementor-templates-modal__header__item',
					onClick: handleClose,
					sx: {
						cursor: 'pointer',
						width: '47px',
						borderInlineStart: 'var(--e-a-border, 1px solid #d5dade)',
						position: 'relative',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						boxSizing: 'content-box',
					},
				}, [
					React.createElement('i', {
						key: 'close-icon',
						className: 'eicon-close',
						'aria-hidden': 'true',
						style: {
							fontSize: '18px',
							transition: 'var(--e-a-transition-hover, all 0.3s)',
							cursor: 'pointer',
						},
						onMouseEnter: (e) => {
							e.target.style.color = 'var(--e-a-color-txt-hover, #5e72e4)';
						},
						onMouseLeave: (e) => {
							e.target.style.color = '';
						},
					}),
					React.createElement('span', {
						key: 'screen-only',
						className: 'elementor-screen-only',
					}, 'Close'),
				])),
			]);

			return React.createElement(Dialog, {
				open: isOpen,
				onClose: handleClose,
				maxWidth: false,
				fullWidth: true,
				sx: {
					'& .MuiDialog-paper': {
						maxWidth: '1200px',
						width: '100%',
						fontFamily: 'var(--e-a-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif)',
						backgroundColor: 'var(--e-a-bg-default, #fff)',
					},
				},
			}, [
				headerElement,
				React.createElement(DialogContent, {
					key: 'content',
					sx: {
						padding: '20px',
						textAlign: 'start',
					},
				}, [
					React.createElement('form', {
						key: 'form',
						id: 'ehcc-import-form-react',
						onSubmit: handleSubmit,
					}, [
						React.createElement(Stack, {
							key: 'fields',
							direction: 'column',
							spacing: 2.5,
						}, [
							React.createElement(TextField, {
								key: 'url',
								fullWidth: true,
								label: 'URL to Import',
								required: true,
								type: 'url',
								value: url,
								onChange: (e) => setUrl(e.target.value),
								placeholder: 'https://example.com/page',
								disabled: isLoading,
							}),
							React.createElement(TextField, {
								key: 'selectors',
								fullWidth: true,
								label: 'CSS Selectors',
								required: true,
								multiline: true,
								rows: 3,
								value: selectors,
								onChange: (e) => setSelectors(e.target.value),
								placeholder: '.hero, .card, #main-content',
								helperText: 'Comma-separated CSS selectors',
								disabled: isLoading,
							}),
							React.createElement(TextField, {
								key: 'timeout',
								fullWidth: true,
								label: 'Timeout (seconds)',
								type: 'number',
								value: timeout,
								onChange: (e) => setTimeout(e.target.value),
								inputProps: { min: 10, max: 300 },
								disabled: isLoading,
							}),
						]),
						statusMessage && React.createElement(Box, {
							key: 'status',
							sx: { mt: 2 },
						}, React.createElement(Alert, {
							severity: statusType || 'info',
						}, statusMessage)),
					]),
				]),
				React.createElement(DialogActions, {
					key: 'actions',
					sx: {
						padding: '10px 20px 20px 20px',
						gap: '15px',
						justifyContent: 'flex-end',
					},
				}, [
					React.createElement(Button, {
						key: 'cancel',
						onClick: handleClose,
						disabled: isLoading,
						variant: 'outlined',
						color: 'secondary',
					}, 'Cancel'),
					React.createElement(Button, {
						key: 'submit',
						type: 'submit',
						form: 'ehcc-import-form-react',
						variant: 'contained',
						color: 'primary',
						disabled: isLoading,
						sx: {
							backgroundColor: '#F3BAFD',
							color: 'rgb(12, 13, 14)',
							'&:hover': {
								backgroundColor: '#F3BAFD',
								color: 'rgb(12, 13, 14)',
							},
						},
					}, isLoading ? 'Starting...' : 'Start Import'),
				]),
			]);
		};

		const ehccImportModalReact = {
			container: null,
			root: null,
			isOpen: false,

			init() {
				console.log('[EHCC Import Modal React] Initializing...');
				this.container = document.createElement('div');
				this.container.id = 'ehcc-import-modal-react-container';
				document.body.appendChild(this.container);

				if (createRoot) {
					this.root = createRoot(this.container);
				}
			},

			open() {
				console.log('[EHCC Import Modal React] Opening modal...');
				if (!this.container) {
					this.init();
				}

				this.isOpen = true;
				this.render();
			},

			close() {
				console.log('[EHCC Import Modal React] Closing modal...');
				this.isOpen = false;
				this.render();
			},

			render() {
				const apiUrl = window.ehccImport?.apiUrl || '/wp-json/html-css-converter/v1/';
				const urlParams = new URLSearchParams(window.location.search);
				const postId = urlParams.get('post') || (window.elementor?.config?.initial_document?.id);
				const modalElement = React.createElement(ImportModal, {
					isOpen: this.isOpen,
					onClose: () => this.close(),
					apiUrl: apiUrl,
					postId: postId,
				});

				if (this.root) {
					this.root.render(modalElement);
				} else {
					ReactDOMRender(modalElement, this.container);
				}
			},
		};

		if (typeof window !== 'undefined') {
			window.ehccImportModalReact = ehccImportModalReact;
			console.log('[EHCC Import Modal React] Modal object attached to window');
		}

		ehccImportModalReact.init();
	}

	waitForReact();
})();
