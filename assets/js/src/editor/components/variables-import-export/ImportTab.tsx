import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { ImportTabProps } from '../../types/components';
import { syncElementorVariablesToLocalStorage } from '../../utils/syncElementorVariables';

export const ImportTab = ({
	apiUrl,
	isLoading,
	setIsLoading,
	statusMessage,
	setStatusMessage,
	statusType,
	setStatusType,
}: ImportTabProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { useState } = React;
	const [importText, setImportText] = useState('');

	const {
		Stack: StackComponent,
		TextField: TextFieldComponent,
		Button: ButtonComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Typography: TypographyComponent,
	} = ui;

	const handleImport = async () => {
		if (!importText.trim()) {
			setStatusMessage('Please paste CSS variables to import.');
			setStatusType('error');
			return;
		}

		setIsLoading(true);
		setStatusMessage(null);
		setStatusType(null);

		try {
			const fullUrl = apiUrl + 'import-variables';
			const payload = { css: importText.trim() };

			const response = await fetch(fullUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(payload),
			});

			const data = await response.json();

			if (data.success) {
				const createdCount = data.created || 0;
				const reusedCount = data.reused || 0;
				const updatedCount = data.updated || 0;

				setStatusMessage(
					`Import complete: ${createdCount} created, ${reusedCount} reused, ${updatedCount} updated.`
				);
				setStatusType('success');
				setImportText('');

				await syncElementorVariablesToLocalStorage(apiUrl);
				window.dispatchEvent(new Event('variables:updated'));
			} else {
				setStatusMessage(data.error || data.message || 'Import failed.');
				setStatusType('error');
			}
		} catch (error) {
			console.error('[EHCC] Import: Error:', error);
			setStatusMessage(
				'Import failed: ' + (error instanceof Error ? error.message : 'Unknown error')
			);
			setStatusType('error');
		} finally {
			setIsLoading(false);
		}
	};

	return (
		<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
			{TypographyComponent && (
				<TypographyComponent variant="body2" color="text.secondary">
					Paste CSS custom properties below to import them as variables.
				</TypographyComponent>
			)}
			<TextFieldComponent
				fullWidth
				multiline
				rows={10}
				value={importText}
				onChange={(e: any) => setImportText(e.target.value)}
				placeholder={`:root {\n\t--primary-color: #ff0000;\n\t--spacing-large: 24px;\n}`}
				disabled={isLoading}
				sx={{
					'& .MuiInputBase-input': {
						fontFamily: 'monospace',
						fontSize: '13px',
					},
				}}
			/>
			<ButtonComponent
				variant="contained"
				color="primary"
				disabled={isLoading || !importText.trim()}
				onClick={handleImport}
				sx={{
					backgroundColor: '#F3BAFD',
					color: 'rgb(12, 13, 14)',
					'&:hover': {
						backgroundColor: '#e0a0ee',
						color: 'rgb(12, 13, 14)',
					},
				}}
			>
				{isLoading ? 'Importing...' : 'Import Variables'}
			</ButtonComponent>
			{statusMessage && (
				<BoxComponent>
					<AlertComponent severity={statusType || 'info'}>
						{statusMessage}
					</AlertComponent>
				</BoxComponent>
			)}
		</StackComponent>
	);
};
