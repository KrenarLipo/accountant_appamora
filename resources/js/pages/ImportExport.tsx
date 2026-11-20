import { useRef, useState, type FormEvent } from 'react';
import { Button, Card, ErrorNotice, Field, Input, PageHeader, Select, cx } from '../components/ui';
import { ApiError, download, upload } from '../lib/api';
import { dateLabel, money, todayIso } from '../lib/format';
import { useWorkspace } from '../lib/workspace';
import type { ImportPreview } from '../types';

function firstOfMonth(): string {
    return `${todayIso().slice(0, 8)}01`;
}

function ExportCard() {
    const workspace = useWorkspace();
    const [range, setRange] = useState({ from: firstOfMonth(), to: todayIso(), type: '' });
    const [busy, setBusy] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    async function exportAs(format: 'xlsx' | 'csv') {
        setBusy(format);
        setError(null);
        try {
            await download(`workspaces/${workspace.id}/export`, { format, ...range }, `transactions.${format}`);
        } catch (caught) {
            setError(caught instanceof ApiError ? (Object.values(caught.errors)[0]?.[0] ?? caught.message) : 'Export failed.');
        } finally {
            setBusy(null);
        }
    }

    return (
        <Card title="Export">
            <p className="mb-4 text-sm text-slate-500">
                Download your transactions for any period. The Excel file also has a Summary sheet with totals, profit/loss and each category against its normal amount.
            </p>
            <div className="grid gap-3 sm:grid-cols-3">
                <Field label="From"><Input type="date" value={range.from} onChange={(event) => setRange({ ...range, from: event.target.value })} /></Field>
                <Field label="To"><Input type="date" value={range.to} onChange={(event) => setRange({ ...range, to: event.target.value })} /></Field>
                <Field label="Include">
                    <Select value={range.type} onChange={(event) => setRange({ ...range, type: event.target.value })}>
                        <option value="">Everything</option>
                        <option value="expense">Expenses only</option>
                        <option value="income">Income only</option>
                        <option value="transfer">Transfers only</option>
                    </Select>
                </Field>
            </div>
            {error && <div className="mt-3"><ErrorNotice message={error} /></div>}
            <div className="mt-4 flex flex-wrap gap-2">
                <Button onClick={() => exportAs('xlsx')} disabled={!!busy}>{busy === 'xlsx' ? 'Preparing…' : 'Download Excel (.xlsx)'}</Button>
                <Button variant="secondary" onClick={() => exportAs('csv')} disabled={!!busy}>{busy === 'csv' ? 'Preparing…' : 'Download CSV'}</Button>
            </div>
        </Card>
    );
}

function ImportCard() {
    const workspace = useWorkspace();
    const input = useRef<HTMLInputElement>(null);
    const [file, setFile] = useState<File | null>(null);
    const [preview, setPreview] = useState<ImportPreview | null>(null);
    const [allowReimport, setAllowReimport] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [done, setDone] = useState<string | null>(null);
    const base = `workspaces/${workspace.id}`;

    function reset() {
        setFile(null);
        setPreview(null);
        setAllowReimport(false);
        if (input.current) {
            input.current.value = '';
        }
    }

    async function check(event: FormEvent) {
        event.preventDefault();
        if (!file) {
            return;
        }
        setBusy(true);
        setError(null);
        setDone(null);
        try {
            const result = await upload<{ data: ImportPreview }>(`${base}/imports/preview`, file);
            setPreview(result.data);
        } catch (caught) {
            setError(caught instanceof ApiError ? (Object.values(caught.errors)[0]?.[0] ?? caught.message) : 'Could not read the file.');
        } finally {
            setBusy(false);
        }
    }

    async function confirm() {
        if (!file) {
            return;
        }
        setBusy(true);
        setError(null);
        try {
            const result = await upload<{ data: { imported: number; skipped: number } }>(`${base}/imports`, file, allowReimport ? { allow_reimport: '1' } : {});
            setDone(`Imported ${result.data.imported} transactions${result.data.skipped ? `, skipped ${result.data.skipped}` : ''}.`);
            reset();
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Import failed.');
        } finally {
            setBusy(false);
        }
    }

    if (!workspace.permissions.record_transactions) {
        return (
            <Card title="Import">
                <p className="text-sm text-slate-500">Your role in this workspace can view data but not import it.</p>
            </Card>
        );
    }

    return (
        <Card title="Import from Excel">
            <ol className="mb-4 flex flex-col gap-1 text-sm text-slate-600">
                <li>1. Download the template — it already lists your categories and accounts.</li>
                <li>2. Copy your numbers into the <strong>Transactions</strong> sheet (one row per payment or income).</li>
                <li>3. Upload it here. You will see every row checked before anything is saved.</li>
            </ol>
            <Button variant="secondary" onClick={() => void download(`${base}/imports/template`, {}, 'accountant-import-template.xlsx')}>
                Download template
            </Button>

            <form onSubmit={check} className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                <Field label="Your file (.xlsx, .xls or .csv, max 5 MB)">
                    <input
                        ref={input}
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        onChange={(event) => { setFile(event.target.files?.[0] ?? null); setPreview(null); setDone(null); }}
                        className="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-medium hover:file:bg-slate-200"
                    />
                </Field>
                <Button type="submit" disabled={!file || busy}>{busy && !preview ? 'Checking…' : 'Check file'}</Button>
            </form>

            {error && <div className="mt-4"><ErrorNotice message={error} /></div>}
            {done && <div className="mt-4 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700" role="status">{done}</div>}

            {preview && preview.missing_columns.length > 0 && (
                <div className="mt-4"><ErrorNotice message={`The file needs these columns in the first row: ${preview.missing_columns.join(', ')}. Use the template to be safe.`} /></div>
            )}

            {preview && preview.missing_columns.length === 0 && (
                <div className="mt-5 flex flex-col gap-4">
                    <div className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                        <div className="rounded-lg bg-brand-50 p-3"><p className="text-xs text-brand-700">Ready to import</p><p className="text-lg font-bold text-brand-700">{preview.summary.ready}</p></div>
                        <div className="rounded-lg bg-slate-50 p-3"><p className="text-xs text-slate-500">Already in Accountant</p><p className="text-lg font-bold">{preview.summary.duplicates}</p></div>
                        <div className="rounded-lg bg-red-50 p-3"><p className="text-xs text-red-700">Rows with problems</p><p className="text-lg font-bold text-red-700">{preview.summary.errors}</p></div>
                        <div className="rounded-lg bg-slate-50 p-3"><p className="text-xs text-slate-500">Total rows</p><p className="text-lg font-bold">{preview.summary.total}</p></div>
                    </div>
                    {preview.summary.new_categories.length > 0 && (
                        <p className="text-sm text-slate-600">New categories that will be created: <strong>{preview.summary.new_categories.join(', ')}</strong></p>
                    )}
                    {preview.already_imported_at && (
                        <label className="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                            <input type="checkbox" className="mt-0.5" checked={allowReimport} onChange={(event) => setAllowReimport(event.target.checked)} />
                            This exact file was already imported on {new Date(preview.already_imported_at).toLocaleString()}. Import it again anyway.
                        </label>
                    )}

                    <div className="max-h-96 overflow-auto rounded-xl border border-slate-200">
                        <table className="w-full text-left text-sm">
                            <thead className="sticky top-0 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th className="p-2">Row</th><th className="p-2">Date</th><th className="p-2">Type</th><th className="p-2">Category</th><th className="p-2 text-right">Amount</th><th className="p-2 text-right">Return</th><th className="p-2">Merchant</th><th className="p-2">Status</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {preview.rows.map((row) => (
                                    <tr key={row.line} className={cx(row.status === 'error' && 'bg-red-50/60', row.status === 'duplicate' && 'text-slate-400')}>
                                        <td className="p-2">{row.line}</td>
                                        <td className="p-2 whitespace-nowrap">{row.date ? dateLabel(row.date) : '—'}</td>
                                        <td className="p-2">{row.type ?? '—'}</td>
                                        <td className="p-2">{row.category ?? '—'}{row.creates_category && <span className="ml-1 text-xs text-brand-700">(new)</span>}</td>
                                        <td className="p-2 text-right whitespace-nowrap">{row.amount ? money(row.amount, workspace.base_currency) : '—'}</td>
                                        <td className="p-2 text-right whitespace-nowrap">{row.return_amount ? money(row.return_amount, workspace.base_currency) : ''}</td>
                                        <td className="p-2">{row.merchant}</td>
                                        <td className="p-2">
                                            {row.status === 'ok' && <span className="text-brand-700">Ready</span>}
                                            {row.status === 'duplicate' && <span>Already exists — skipped</span>}
                                            {row.status === 'error' && <span className="text-red-700">{row.errors.join(' ')}</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button onClick={confirm} disabled={busy || preview.summary.ready === 0 || (!!preview.already_imported_at && !allowReimport)}>
                            {busy ? 'Importing…' : `Import ${preview.summary.ready} transactions`}
                        </Button>
                        <Button variant="secondary" onClick={reset}>Cancel</Button>
                    </div>
                    {preview.summary.errors > 0 && <p className="text-xs text-slate-500">Rows with problems are skipped. Fix them in Excel and import the file again — rows already imported are recognised and skipped.</p>}
                </div>
            )}
        </Card>
    );
}

export default function ImportExport() {
    return (
        <div className="flex flex-col gap-6">
            <PageHeader title="Import & export" subtitle="Bring in your existing Excel numbers, or take your data out at any time." />
            <ImportCard />
            <ExportCard />
        </div>
    );
}
