import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { formatVariablesAsCss, loadVariablesFromStorage } from '../../hooks/useVariablesImportExport';
import { ExportTabProps } from '../../types/components';

export const ExportTab = ({
	statusMessage,
	setStatusMessage,
	statusType,
	setStatusType,
}: ExportTabProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { useMemo } = React;

	const {
		Stack: StackComponent,
		TextField: TextFieldComponent,
		Button: ButtonComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Typography: TypographyComponent,
	} = ui;

	const exportCss = useMemo(() => {
		const variables = loadVariablesFromStorage();
		return formatVariablesAsCss(variables);
	}, []);

	const hasVariables = exportCss.length > 0;

	const handleCopy = async () => {
		try {
			await navigator.clipboard.writeText(exportCss);
			setStatusMessage('Copied to clipboard.');
			setStatusType('success');
		} catch {
			setStatusMessage('Failed to copy. Please select and copy manually.');
			setStatusType('error');
		}
	};

	return (
		<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
			{TypographyComponent && (
				<TypographyComponent variant="body2" color="text.secondary">
					{hasVariables
						? 'Copy the CSS custom properties below to use or share your variables.'
						: 'No variables to export. Create variables in the Variables Manager first.'}
				</TypographyComponent>
			)}
			{hasVariables && (
				<>
					<TextFieldComponent
						fullWidth
						multiline
						rows={10}
						value={exportCss}
						InputProps={{ readOnly: true }}
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
						onClick={handleCopy}
						sx={{
							backgroundColor: '#F3BAFD',
							color: 'rgb(12, 13, 14)',
							'&:hover': {
								backgroundColor: '#e0a0ee',
								color: 'rgb(12, 13, 14)',
							},
						}}
					>
						Copy to Clipboard
					</ButtonComponent>
				</>
			)}
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
