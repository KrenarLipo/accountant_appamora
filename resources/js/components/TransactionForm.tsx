import { useState, type FormEvent } from 'react';
import { api, ApiError } from '../lib/api';
import { todayIso } from '../lib/format';
import type { AccountBalance, Category, Transaction, TransactionType } from '../types';
import { Button, Field, Input, Select, cx } from './ui';

interface Props {
    workspaceId: number;
    accounts: AccountBalance[];
    categories: Category[];
    transaction?: Transaction | null;
    onSaved: () => void;
}

export default function TransactionForm({ workspaceId, accounts, categories, transaction, onSaved }: Props) {
    const activeAccounts = accounts.filter((account) => account.is_active || account.id === transaction?.account.id);
    const [form, setForm] = useState({
        type: (transaction?.type ?? 'expense') as TransactionType,
        amount: transaction ? Number(transaction.amount).toFixed(2) : '',
        transaction_date: transaction?.transaction_date ?? todayIso(),
        account_id: String(transaction?.account.id ?? activeAccounts[0]?.id ?? ''),
        transfer_account_id: String(transaction?.transfer_account?.id ?? ''),
        category_id: String(transaction?.category?.id ?? ''),
        merchant: transaction?.merchant ?? '',
        description: transaction?.description ?? '',
    });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [message, setMessage] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const set = (key: keyof typeof form) => (event: { target: { value: string } }) => setForm({ ...form, [key]: event.target.value });
    const matchingCategories = categories.filter((category) => category.type === form.type);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setMessage(null);
        const isTransfer = form.type === 'transfer';
        const body = {
            type: form.type,
            amount: form.amount,
            transaction_date: form.transaction_date,
            account_id: Number(form.account_id),
            transfer_account_id: isTransfer ? Number(form.transfer_account_id) || null : undefined,
            category_id: isTransfer ? undefined : Number(form.category_id) || null,
            merchant: form.merchant || null,
            description: form.description || null,
        };
        try {
            await api(`workspaces/${workspaceId}/transactions${transaction ? `/${transaction.id}` : ''}`, {
                method: transaction ? 'PUT' : 'POST',
                body,
            });
            onSaved();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setMessage(Object.keys(caught.errors).length ? null : caught.message);
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <div className="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1" role="radiogroup" aria-label="Transaction type">
                {(['expense', 'income', 'transfer'] as const).map((type) => (
                    <button
                        key={type}
                        type="button"
                        role="radio"
                        aria-checked={form.type === type}
                        onClick={() => setForm({ ...form, type, category_id: '' })}
                        className={cx('rounded-md py-1.5 text-sm font-medium capitalize', form.type === type ? 'bg-white text-ink shadow-sm' : 'text-slate-500')}
                    >
                        {type}
                    </button>
                ))}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Amount" error={errors.amount?.[0]}>
                    <Input type="number" inputMode="decimal" step="0.01" min="0.01" value={form.amount} onChange={set('amount')} required autoFocus />
                </Field>
                <Field label="Date" error={errors.transaction_date?.[0]}>
                    <Input type="date" value={form.transaction_date} onChange={set('transaction_date')} required />
                </Field>
                <Field label={form.type === 'transfer' ? 'From account' : 'Account'} error={errors.account_id?.[0]}>
                    <Select value={form.account_id} onChange={set('account_id')} required>
                        {activeAccounts.map((account) => (
                            <option key={account.id} value={account.id}>{account.name}</option>
                        ))}
                    </Select>
                </Field>
                {form.type === 'transfer' ? (
                    <Field label="To account" error={errors.transfer_account_id?.[0]}>
                        <Select value={form.transfer_account_id} onChange={set('transfer_account_id')} required>
                            <option value="">Choose…</option>
                            {activeAccounts
                                .filter((account) => String(account.id) !== form.account_id)
                                .map((account) => (
                                    <option key={account.id} value={account.id}>{account.name}</option>
                                ))}
                        </Select>
                    </Field>
                ) : (
                    <Field label="Category" error={errors.category_id?.[0]}>
                        <Select value={form.category_id} onChange={set('category_id')}>
                            <option value="">Uncategorized</option>
                            {matchingCategories.map((category) => (
                                <option key={category.id} value={category.id}>{category.name}</option>
                            ))}
                        </Select>
                    </Field>
                )}
            </div>

            {form.type !== 'transfer' && (
                <Field label={form.type === 'income' ? 'From (payer)' : 'Merchant / payee'} error={errors.merchant?.[0]}>
                    <Input value={form.merchant} onChange={set('merchant')} placeholder={form.type === 'income' ? 'e.g. Employer' : 'e.g. Supermarket'} />
                </Field>
            )}
            <Field label="Note" error={errors.description?.[0]}>
                <Input value={form.description} onChange={set('description')} placeholder="Optional" />
            </Field>

            {message && <p className="text-sm text-red-600">{message}</p>}

            <Button type="submit" disabled={saving}>
                {saving ? 'Saving…' : transaction ? 'Save changes' : 'Add transaction'}
            </Button>
        </form>
    );
}
