import { useState, type FormEvent } from 'react';
import { Button, Card, ErrorNotice, Field, Input, Loading, Modal, PageHeader, Select } from '../components/ui';
import { api, ApiError } from '../lib/api';
import { accountTypeLabels, money } from '../lib/format';
import { useApi } from '../lib/useApi';
import { useWorkspace } from '../lib/workspace';
import type { AccountBalance } from '../types';

function AccountForm({ workspaceId, account, onSaved }: { workspaceId: number; account: AccountBalance | null; onSaved: () => void }) {
    const [form, setForm] = useState({
        name: account?.name ?? '',
        type: account?.type ?? 'bank',
        opening_balance: account?.opening_balance ?? '0',
        is_active: account?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        try {
            await api(`workspaces/${workspaceId}/accounts${account ? `/${account.id}` : ''}`, {
                method: account ? 'PUT' : 'POST',
                body: { ...form, opening_balance: Number(form.opening_balance).toFixed(2) },
            });
            onSaved();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <Field label="Name" error={errors.name?.[0]}>
                <Input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required placeholder="e.g. Raiffeisen current account" />
            </Field>
            <Field label="Type" error={errors.type?.[0]}>
                <Select value={form.type} onChange={(event) => setForm({ ...form, type: event.target.value as AccountBalance['type'] })}>
                    {Object.entries(accountTypeLabels).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </Select>
            </Field>
            <Field label="Opening balance" error={errors.opening_balance?.[0]} hint="The balance before your first recorded transaction. Use a negative number for card debt.">
                <Input type="number" step="0.01" value={form.opening_balance} onChange={(event) => setForm({ ...form, opening_balance: event.target.value })} required />
            </Field>
            {account && (
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />
                    Active (inactive accounts are hidden when adding transactions)
                </label>
            )}
            <Button type="submit" disabled={saving}>{saving ? 'Saving…' : account ? 'Save account' : 'Add account'}</Button>
        </form>
    );
}

export default function Accounts() {
    const workspace = useWorkspace();
    const base = `workspaces/${workspace.id}`;
    const accounts = useApi<{ data: AccountBalance[] }>(`${base}/accounts`);
    const [editing, setEditing] = useState<AccountBalance | null>(null);
    const [creating, setCreating] = useState(false);
    const [notice, setNotice] = useState<string | null>(null);
    const canManage = workspace.permissions.manage_finances;

    async function saved() {
        setCreating(false);
        setEditing(null);
        await accounts.reload();
    }

    async function remove(account: AccountBalance) {
        if (!window.confirm(`Remove ${account.name}? Its past transactions stay in your reports.`)) {
            return;
        }
        try {
            await api(`${base}/accounts/${account.id}`, { method: 'DELETE' });
            setNotice(null);
            await accounts.reload();
        } catch (caught) {
            setNotice(caught instanceof ApiError ? caught.message : 'Could not remove the account.');
        }
    }

    const total = accounts.data?.data.reduce((sum, account) => sum + Number(account.balance), 0) ?? 0;

    return (
        <div>
            <PageHeader
                title="Accounts"
                subtitle={accounts.data ? `Total across accounts: ${money(total, workspace.base_currency)}` : undefined}
                actions={canManage && <Button onClick={() => setCreating(true)}>+ Add account</Button>}
            />
            {notice && <div className="mb-4"><ErrorNotice message={notice} /></div>}
            {accounts.error && <ErrorNotice message={accounts.error} />}
            {!accounts.data && <Loading />}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {accounts.data?.data.map((account) => (
                    <Card key={account.id} className={account.is_active ? '' : 'opacity-60'}>
                        <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
                            {accountTypeLabels[account.type]}{!account.is_active && ' · inactive'}
                        </p>
                        <h2 className="mt-1 font-semibold text-ink">{account.name}</h2>
                        <p className={`mt-3 text-2xl font-bold ${Number(account.balance) < 0 ? 'text-red-600' : 'text-ink'}`}>{money(account.balance, account.currency)}</p>
                        {canManage && (
                            <div className="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                                <Button variant="ghost" className="px-2 py-1 text-xs" onClick={() => setEditing(account)}>Edit</Button>
                                <Button variant="ghost" className="px-2 py-1 text-xs text-red-600" onClick={() => remove(account)}>Remove</Button>
                            </div>
                        )}
                    </Card>
                ))}
            </div>

            <Modal title={editing ? 'Edit account' : 'Add account'} open={creating || !!editing} onClose={() => { setCreating(false); setEditing(null); }}>
                <AccountForm key={editing?.id ?? 'new'} workspaceId={workspace.id} account={editing} onSaved={saved} />
            </Modal>
        </div>
    );
}
