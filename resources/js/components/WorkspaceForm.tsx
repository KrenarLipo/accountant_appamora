import { useState, type FormEvent } from 'react';
import { api, ApiError } from '../lib/api';
import type { Workspace } from '../types';
import { Button, Field, Input, Select } from './ui';

const currencies = ['EUR', 'ALL', 'USD', 'GBP', 'CHF'];

function guessTimezone(): string {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Europe/Tirane';
    } catch {
        return 'Europe/Tirane';
    }
}

export default function WorkspaceForm({ onCreated, submitLabel = 'Create workspace' }: { onCreated: (workspace: Workspace) => void; submitLabel?: string }) {
    const [form, setForm] = useState({
        name: '',
        type: 'personal',
        base_currency: 'EUR',
        timezone: guessTimezone(),
        country: '',
        account_name: 'Main account',
        opening_balance: '0',
    });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const update = (key: keyof typeof form) => (event: { target: { value: string } }) => setForm({ ...form, [key]: event.target.value });

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        try {
            const result = await api<{ data: Workspace }>('workspaces', {
                method: 'POST',
                body: { ...form, country: form.country || null },
            });
            onCreated(result.data);
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
            <div className="grid grid-cols-2 gap-3">
                {(['personal', 'business'] as const).map((type) => (
                    <button
                        key={type}
                        type="button"
                        onClick={() => setForm({ ...form, type })}
                        className={`rounded-xl border-2 p-4 text-left ${form.type === type ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-slate-300'}`}
                    >
                        <p className="font-semibold text-ink">{type === 'personal' ? 'Personal' : 'Business'}</p>
                        <p className="mt-1 text-xs text-slate-500">{type === 'personal' ? 'Household and personal money' : 'A company, shop or freelance work'}</p>
                    </button>
                ))}
            </div>
            <Field label="Workspace name" error={errors.name?.[0]}>
                <Input value={form.name} onChange={update('name')} placeholder={form.type === 'personal' ? 'My money' : 'My company'} required />
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Currency" error={errors.base_currency?.[0]} hint="Cannot be changed later">
                    <Select value={form.base_currency} onChange={update('base_currency')}>
                        {currencies.map((currency) => (
                            <option key={currency}>{currency}</option>
                        ))}
                    </Select>
                </Field>
                <Field label="Time zone" error={errors.timezone?.[0]}>
                    <Input value={form.timezone} onChange={update('timezone')} required />
                </Field>
                <Field label="First account name" error={errors.account_name?.[0]}>
                    <Input value={form.account_name} onChange={update('account_name')} />
                </Field>
                <Field label="Current balance" error={errors.opening_balance?.[0]} hint="What is in that account today">
                    <Input type="number" step="0.01" value={form.opening_balance} onChange={update('opening_balance')} />
                </Field>
            </div>
            <Button type="submit" disabled={saving}>
                {saving ? 'Creating…' : submitLabel}
            </Button>
        </form>
    );
}
