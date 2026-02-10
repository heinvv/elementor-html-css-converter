import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { ExportTabProps } from '../../types/components';

interface ClassesExportTabProps extends ExportTabProps {
	apiUrl: string;
}

export const ClassesExportTab = ({
	apiUrl,
	statusMessage,
	setStatusMessage,
	statusType,
	setStatusType,
}: ClassesExportTabProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { useState, useEffect } = React;
	const [exportCss, setExportCss] = useState('');
	const [isLoadingExport, setIsLoadingExport] = useState(true);
	const [totalClasses, setTotalClasses] = useState(0);

	const {
		Stack: StackComponent,
		TextField: TextFieldComponent,
		Button: ButtonComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Typography: TypographyComponent,
	} = ui;

	useEffect(() => {
		const fetchExportCss = async () => {
			setIsLoadingExport(true);
			try {
				const fullUrl = apiUrl + 'export-classes?context=preview';
				const response = await fetch(fullUrl, {
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
					},
				});

				const data = await response.json();

				if (data.success) {
					setExportCss(data.css || '');
					setTotalClasses(data.total_classes || 0);
				} else {
					setStatusMessage(data.error || 'Failed to load classes.');
					setStatusType('error');
				}
			} catch (error) {
				setStatusMessage(
					'Failed to load classes: ' + (error instanceof Error ? error.message : 'Unknown error')
				);
				setStatusType('error');
			} finally {
				setIsLoadingExport(false);
			}
		};

		fetchExportCss();
	}, []);

	const hasClasses = exportCss.length > 0;

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

	if (isLoadingExport) {
		return (
			<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
				{TypographyComponent && (
					<TypographyComponent variant="body2" color="text.secondary">
						Loading classes...
					</TypographyComponent>
				)}
			</StackComponent>
		);
	}

	return (
		<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
			{TypographyComponent && (
				<TypographyComponent variant="body2" color="text.secondary">
					{hasClasses
						? `Copy the CSS below to share your ${totalClasses} global classes.`
						: 'No classes to export. Create classes in the Class Manager first.'}
				</TypographyComponent>
			)}
			{hasClasses && (
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
