import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { useImportForm } from '../hooks/useImportForm';
import { useImportPolling } from '../hooks/useImportPolling';
import { useImportSubmit } from '../hooks/useImportSubmit';
import { ModalHeader } from './ModalHeader';
import { ModalContent } from './ModalContent';
import { ModalActions } from './ModalActions';
import { HtmlImportTab } from './HtmlImportTab';
import { ImportModalProps } from '../types/components';

export const ImportModal = ({ isOpen, onClose, apiUrl, postId }: ImportModalProps) => {
	const React = getReact();
	if (!React) {
		return null;
	}

	const ui = getElementorUI();
	if (!ui) {
		return null;
	}

	const formState = useImportForm();
	if (!formState) {
		return null;
	}

	const {
		url,
		setUrl,
		selectors,
		setSelectors,
		timeout,
		setTimeout,
		isLoading,
		setIsLoading,
		statusMessage,
		setStatusMessage,
		statusType,
		setStatusType,
		diagnostics,
		setDiagnostics,
		htmlContent,
		setHtmlContent,
		activeTab,
		setActiveTab,
		resetForm,
	} = formState;

	const polling = useImportPolling({
		apiUrl,
		setStatusMessage,
		setStatusType,
		setIsLoading,
		setDiagnostics,
		onClose,
		isOpen,
		postId,
	});

	if (!polling) {
		return null;
	}

	const { startPolling, stopPolling } = polling;

	const submit = useImportSubmit({
		url,
		selectors,
		timeout,
		apiUrl,
		postId,
		setIsLoading,
		setStatusMessage,
		setStatusType,
		startPolling,
	});

	const { handleSubmit } = submit;

	const handleClose = () => {
		stopPolling();
		resetForm();
		onClose();
	};

	const handleTabChange = (_event: any, newValue: number) => {
		setStatusMessage(null);
		setStatusType(null);
		setDiagnostics(null);
		setActiveTab(newValue);
	};

	const handleHtmlSubmit = async () => {
		if (!htmlContent.trim()) {
			setStatusMessage('Please paste HTML content');
			setStatusType('error');
			return;
		}

		setIsLoading(true);
		setStatusMessage(null);
		setStatusType(null);

		try {
			const convertResponse = await fetch(apiUrl + 'convert-html', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					html: htmlContent,
					save_as_template: true,
					import_variables: true,
					import_classes: true,
				}),
			});

			if (!convertResponse.ok) {
				const errorText = await convertResponse.text();
				setStatusMessage(`Failed to convert: ${errorText}`);
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			const convertData = await convertResponse.json();
			const templateId = convertData.template_id;

			if (!templateId) {
				setStatusMessage('Conversion succeeded but no template ID returned');
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			const $e = (window as any).$e;
			if (!$e || !$e.run) {
				setStatusMessage('Elementor editor not available');
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			const templateUrl = `${apiUrl.replace(/\/$/, '')}/import-template/${templateId}`;
			const nonce = (window as any).ehccImport?.nonce;
			const fetchOpts: RequestInit = {
				credentials: 'same-origin',
				headers: nonce ? { 'X-WP-Nonce': nonce } : {},
			};

			const templateRes = await fetch(templateUrl, fetchOpts);

			if (!templateRes.ok) {
				const errBody = await templateRes.text();
				let errMsg = `Failed to load template data (${templateRes.status})`;
				try {
					const parsed = JSON.parse(errBody);
					if (parsed.message) errMsg += `: ${parsed.message}`;
				} catch {
					if (errBody) errMsg += `: ${errBody.slice(0, 100)}`;
				}
				setStatusMessage(errMsg);
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			const rawData = await templateRes.json();
			const templateData = rawData?.data ?? rawData;
			const content = templateData?.content;
			const importData = {
				content: Array.isArray(content) ? content : (content ? [content] : []),
				page_settings: templateData?.page_settings ?? {},
			};

			if (!importData.content.length) {
				setStatusMessage('Template has no content to import');
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			$e.run('document/elements/import', {
				model: new (window as any).Backbone.Model({ title: 'Imported' }),
				data: importData,
				options: { withPageSettings: false },
			});

			setIsLoading(false);
			handleClose();

			if ((window as any).elementor?.notifications) {
				(window as any).elementor.notifications.showToast({
					message: 'HTML imported successfully.',
					type: 'success',
				});
			}
		} catch (error) {
			setStatusMessage('Failed to import HTML: ' + (error instanceof Error ? error.message : 'Unknown error'));
			setStatusType('error');
			setIsLoading(false);
		}
	};

	if (!isOpen) {
		return null;
	}

	const {
		Dialog: DialogComponent,
		Box: BoxComponent,
		Tab: TabComponent,
		Tabs: TabsComponent,
	} = ui;

	return (
		<DialogComponent
			open={isOpen}
			onClose={handleClose}
			maxWidth={false}
			fullWidth={true}
			sx={{
				'& .MuiDialog-paper': {
					maxWidth: '1200px',
					width: '100%',
					fontFamily: 'var(--e-a-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif)',
					backgroundColor: 'var(--e-a-bg-default, #fff)',
				},
			}}
		>
			<ModalHeader onClose={handleClose} />

			{TabsComponent && TabComponent ? (
				<BoxComponent sx={{ borderBottom: 1, borderColor: 'divider' }}>
					<TabsComponent
						value={activeTab}
						onChange={handleTabChange}
						variant="fullWidth"
					>
						<TabComponent label="Import from HTML" />
						<TabComponent label="Import from URL" />
					</TabsComponent>
				</BoxComponent>
			) : (
				<BoxComponent
					sx={{
						display: 'flex',
						borderBottom: 'var(--e-a-border, 1px solid #d5dade)',
					}}
				>
					<BoxComponent
						onClick={() => handleTabChange(null, 0)}
						sx={{
							flex: 1,
							padding: '10px',
							textAlign: 'center',
							cursor: 'pointer',
							fontWeight: activeTab === 0 ? 'bold' : 'normal',
							borderBottom: activeTab === 0 ? '2px solid #F3BAFD' : 'none',
						}}
					>
						Import from HTML
					</BoxComponent>
					<BoxComponent
						onClick={() => handleTabChange(null, 1)}
						sx={{
							flex: 1,
							padding: '10px',
							textAlign: 'center',
							cursor: 'pointer',
							fontWeight: activeTab === 1 ? 'bold' : 'normal',
							borderBottom: activeTab === 1 ? '2px solid #F3BAFD' : 'none',
						}}
					>
						Import from URL
					</BoxComponent>
				</BoxComponent>
			)}

			{activeTab === 0 && (
				<HtmlImportTab
					htmlContent={htmlContent}
					setHtmlContent={setHtmlContent}
					isLoading={isLoading}
					statusMessage={statusMessage}
					statusType={statusType}
					onClose={handleClose}
					onSubmit={handleHtmlSubmit}
				/>
			)}

			{activeTab === 1 && (
				<>
					<ModalContent
						url={url}
						setUrl={setUrl}
						selectors={selectors}
						setSelectors={setSelectors}
						timeout={timeout}
						setTimeout={setTimeout}
						isLoading={isLoading}
						statusMessage={statusMessage}
						statusType={statusType}
						diagnostics={diagnostics}
						onSubmit={handleSubmit}
					/>
					<ModalActions
						onClose={handleClose}
						onSubmit={handleSubmit}
						isLoading={isLoading}
					/>
				</>
			)}
		</DialogComponent>
	);
};
