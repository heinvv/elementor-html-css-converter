import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { AssessmentResult, ImportTabProps, ResolutionAction } from '../../types/components';
import { syncElementorVariablesToLocalStorage } from '../../utils/syncElementorVariables';
import { refreshVariablesPanel } from '../../utils/refreshVariablesPanel';
import { parseCssVariables } from '../../utils/parseCssVariables';
import { assessVariableConflicts } from '../../utils/assessVariableConflicts';
import { ConflictAssessment } from './ConflictAssessment';

type ImportPhase = 'input' | 'assessing' | 'importing';

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
	const [phase, setPhase] = useState<ImportPhase>('input');
	const [assessment, setAssessment] = useState<AssessmentResult | null>(null);
	const [importedVariables, setImportedVariables] = useState<Record<string, { name: string; value: string }> | null>(null);

	const {
		Stack: StackComponent,
		TextField: TextFieldComponent,
		Button: ButtonComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Typography: TypographyComponent,
	} = ui;

	const executeImport = async (resolutions: Record<string, ResolutionAction>, renameMap: Record<string, string> = {}) => {
		setIsLoading(true);
		setStatusMessage(null);
		setStatusType(null);
		setPhase('importing');

		try {
			const fullUrl = apiUrl + 'import-variables';
			const payload = {
				css: importText.trim(),
				resolutions,
				rename_map: renameMap,
			};

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
				const reactivatedCount = data.reactivated || 0;

				const parts: string[] = [];
				if (createdCount > 0) parts.push(`${createdCount} created`);
				if (reusedCount > 0) parts.push(`${reusedCount} reused`);
				if (updatedCount > 0) parts.push(`${updatedCount} updated`);
				if (reactivatedCount > 0) parts.push(`${reactivatedCount} reactivated`);

				setStatusMessage(`Import complete: ${parts.join(', ')}.`);
				setStatusType('success');
				setImportText('');
				setImportedVariables(data.variables || null);

				await syncElementorVariablesToLocalStorage(apiUrl);
				window.dispatchEvent(new Event('variables:updated'));
				refreshVariablesPanel();
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
			setPhase('input');
			setAssessment(null);
		}
	};

	const handleImportClick = () => {
		if (!importText.trim()) {
			setStatusMessage('Please paste CSS variables to import.');
			setStatusType('error');
			return;
		}

		setStatusMessage(null);
		setStatusType(null);
		setImportedVariables(null);

		const parsed = parseCssVariables(importText);

		if (parsed.length === 0) {
			setStatusMessage('No CSS variables found in the input.');
			setStatusType('error');
			return;
		}

		const result = assessVariableConflicts(parsed);

		if (result.hasConflicts) {
			setAssessment(result);
			setPhase('assessing');
			return;
		}

		executeImport(result.autoResolutions);
	};

	const handleAssessmentConfirm = (resolutions: Record<string, ResolutionAction>, renameMap: Record<string, string>) => {
		executeImport(resolutions, renameMap);
	};

	const handleAssessmentCancel = () => {
		setPhase('input');
		setAssessment(null);
	};

	if (phase === 'assessing' && assessment) {
		return (
			<ConflictAssessment
				assessment={assessment}
				onConfirm={handleAssessmentConfirm}
				onCancel={handleAssessmentCancel}
				isLoading={isLoading}
			/>
		);
	}

	const importSucceeded = statusType === 'success' && importedVariables;

	return (
		<StackComponent direction="column" spacing={2} sx={{ padding: '20px' }}>
			{!importSucceeded && TypographyComponent && (
				<TypographyComponent variant="body2" color="text.secondary">
					Paste CSS custom properties below to import them as variables.
				</TypographyComponent>
			)}
			{!importSucceeded && (
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
			)}
			{!importSucceeded && (
				<ButtonComponent
					variant="contained"
					color="primary"
					disabled={isLoading || !importText.trim()}
					onClick={handleImportClick}
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
			)}
			{statusMessage && (
				<BoxComponent>
					<AlertComponent
						severity={statusType || 'info'}
						sx={importSucceeded ? { fontSize: '14px', padding: '16px 20px' } : {}}
					>
						<StackComponent direction="column" spacing={1}>
							{TypographyComponent && (
								<TypographyComponent variant="subtitle2" sx={{ fontWeight: 600 }}>
									{statusMessage}
								</TypographyComponent>
							)}
							{importSucceeded && (
								<BoxComponent
									component="ul"
									sx={{
										margin: 0,
										paddingLeft: '20px',
										listStyleType: 'disc',
									}}
								>
									{Object.entries(importedVariables).map(([label, variable]) => (
										<BoxComponent
											component="li"
											key={label}
											sx={{ fontFamily: 'monospace', fontSize: '13px', paddingBlock: '2px' }}
										>
											{variable.name}: {variable.value}
										</BoxComponent>
									))}
								</BoxComponent>
							)}
						</StackComponent>
					</AlertComponent>
				</BoxComponent>
			)}
			{importSucceeded && (
				<ButtonComponent
					variant="outlined"
					size="small"
					onClick={() => {
						setStatusMessage(null);
						setStatusType(null);
						setImportedVariables(null);
					}}
				>
					Import More
				</ButtonComponent>
			)}
		</StackComponent>
	);
};
