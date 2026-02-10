import { getReact } from '../../utils/getReact';
import { getElementorUI } from '../../utils/getElementorUI';
import { AssessmentResult, ImportTabProps, ResolutionAction } from '../../types/components';
import { reloadElementorVariablesService } from '../../utils/reloadElementorVariablesService';
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
	onClose,
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
	const [importedVariables, setImportedVariables] = useState<Record<string, { name: string; value: string; original_name?: string | null }> | null>(null);
	const [skippedVariables, setSkippedVariables] = useState<Array<{ name: string; value: string }>>([]);
	const [unregisteredFonts, setUnregisteredFonts] = useState<Array<{ name: string; font: string }>>([]);

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
				const skipped: Array<{ name: string; value: string }> = data.skipped_variables || [];

				const parts: string[] = [];
				if (createdCount > 0) parts.push(`${createdCount} created`);
				if (updatedCount > 0) parts.push(`${updatedCount} updated`);
				if (reactivatedCount > 0) parts.push(`${reactivatedCount} reactivated`);
				if (reusedCount > 0) parts.push(`${reusedCount} skipped`);
				if (skipped.length > 0) parts.push(`${skipped.length} not imported`);

				const unregistered: Array<{ name: string; font: string }> = data.unregistered_fonts || [];

				setStatusMessage(`Import complete: ${parts.join(', ')}.`);
				setStatusType('success');
				setImportText('');
				setImportedVariables(data.variables || null);
				setSkippedVariables(skipped);
				setUnregisteredFonts(unregistered);

				await reloadElementorVariablesService();
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
		setSkippedVariables([]);
		setUnregisteredFonts([]);

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
		<>
			<TypographyComponent variant="body2" color="text.secondary">
				Paste CSS custom properties below to import them as variables.
			</TypographyComponent>
			<TypographyComponent variant="body2" sx={{ color: 'error.main' }}>
				Note: It would be great to also integrate the JSON import / export functionality here.
			</TypographyComponent>
		</>
		)}
			{!importSucceeded && (
				<TextFieldComponent
					fullWidth
					multiline
					rows={6}
					value={importText}
					onChange={(e: any) => setImportText(e.target.value)}
					placeholder={`--primary-color: #ff0000;\n--spacing-large: 24px;\n--heading-font: "Roboto";`}
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
									{Object.entries(importedVariables).map(([label, rawVar]) => {
										const variable = rawVar as { name: string; value: string; original_name?: string | null };
										return (
											<BoxComponent
												component="li"
												key={label}
												sx={{ fontFamily: 'monospace', fontSize: '13px', paddingBlock: '2px' }}
											>
												{variable.name}: {variable.value}
												{variable.original_name && (
													<BoxComponent
														component="span"
														sx={{ fontStyle: 'italic', color: 'text.secondary', marginLeft: '8px' }}
													>
														(renamed from {variable.original_name})
													</BoxComponent>
												)}
											</BoxComponent>
										);
									})}
								</BoxComponent>
							)}
					</StackComponent>
				</AlertComponent>
			</BoxComponent>
		)}
		{importSucceeded && skippedVariables.length > 0 && (
			<BoxComponent>
				<AlertComponent
					severity="warning"
					sx={{ fontSize: '14px', padding: '16px 20px' }}
				>
					<StackComponent direction="column" spacing={1}>
						{TypographyComponent && (
							<TypographyComponent variant="subtitle2" sx={{ fontWeight: 600 }}>
								{skippedVariables.length} variable{skippedVariables.length === 1 ? '' : 's'} not imported (unsupported type):
							</TypographyComponent>
						)}
						<BoxComponent
							component="ul"
							sx={{
								margin: 0,
								paddingLeft: '20px',
								listStyleType: 'disc',
							}}
						>
							{skippedVariables.map((variable) => (
								<BoxComponent
									component="li"
									key={variable.name}
									sx={{ fontFamily: 'monospace', fontSize: '13px', paddingBlock: '2px' }}
								>
									{variable.name}: {variable.value}
								</BoxComponent>
							))}
						</BoxComponent>
					</StackComponent>
				</AlertComponent>
			</BoxComponent>
		)}
		{importSucceeded && unregisteredFonts.length > 0 && (
			<BoxComponent>
				<AlertComponent
					severity="info"
					sx={{ fontSize: '14px', padding: '16px 20px' }}
				>
					<StackComponent direction="column" spacing={1}>
						{TypographyComponent && (
							<TypographyComponent variant="subtitle2" sx={{ fontWeight: 600 }}>
								{unregisteredFonts.length} font{unregisteredFonts.length === 1 ? '' : 's'} not available in Elementor:
							</TypographyComponent>
						)}
						<BoxComponent
							component="ul"
							sx={{
								margin: 0,
								paddingLeft: '20px',
								listStyleType: 'disc',
							}}
						>
							{unregisteredFonts.map((entry: { name: string; font: string }) => (
								<BoxComponent
									component="li"
									key={entry.name}
									sx={{ fontFamily: 'monospace', fontSize: '13px', paddingBlock: '2px' }}
								>
									{entry.font} ({entry.name})
								</BoxComponent>
							))}
						</BoxComponent>
						{TypographyComponent && (
							<TypographyComponent variant="body2" color="text.secondary">
								These font variables were imported but may not render correctly. You can replace them in the Variables Manager.
							</TypographyComponent>
						)}
					</StackComponent>
				</AlertComponent>
			</BoxComponent>
		)}
		{importSucceeded && (
				<StackComponent direction="row" spacing={1}>
					<ButtonComponent
						variant="outlined"
						size="small"
					onClick={() => {
						setStatusMessage(null);
						setStatusType(null);
						setImportedVariables(null);
						setSkippedVariables([]);
						setUnregisteredFonts([]);
					}}
						sx={{ width: '50%' }}
					>
						Import More
					</ButtonComponent>
					<ButtonComponent
						variant="contained"
						size="small"
						onClick={onClose}
						sx={{
							width: '50%',
							backgroundColor: '#F3BAFD',
							color: 'rgb(12, 13, 14)',
							'&:hover': {
								backgroundColor: '#e0a0ee',
								color: 'rgb(12, 13, 14)',
							},
						}}
					>
						Close modal
					</ButtonComponent>
				</StackComponent>
			)}
		</StackComponent>
	);
};
