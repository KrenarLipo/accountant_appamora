import { useState, type FormEvent } from 'react';
import { Button, Card, ErrorNotice, Field, Input, Loading, Modal, PageHeader, Select } from '../components/ui';
import { api, ApiError } from '../lib/api';
import { money } from '../lib/format';
import { useApi } from '../lib/useApi';
import { useWorkspace } from '../lib/workspace';
import type { Category, CategoryType } from '../types';

function CategoryForm({ workspaceId, category, defaultType, onSaved }: { workspaceId: number; category: Category | null; defaultType: CategoryType; onSaved: () => void }) {
    const [form, setForm] = useState({
        name: category?.name ?? '',
        type: category?.type ?? defaultType,
        color: category?.color ?? '#10b981',
        normal_amount: category?.normal_amount ? Number(category.normal_amount).toFixed(2) : '',
        normal_tolerance: String(category?.normal_tolerance ?? 20),
    });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [message, setMessage] = useState<string | null>(null);

    async function submit(event: FormEvent) {
        event.preventDefault();
        try {
            await api(`workspaces/${workspaceId}/categories${category ? `/${category.id}` : ''}`, {
                method: category ? 'PUT' : 'POST',
                body: { ...form, normal_amount: form.normal_amount || null, normal_tolerance: Number(form.normal_tolerance) || 20 },
            });
            onSaved();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setMessage(Object.keys(caught.errors).length ? null : caught.message);
            }
        }
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <Field label="Name" error={errors.name?.[0]}>
                <Input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required />
            </Field>
            <div className="grid grid-cols-2 gap-4">
                <Field label="Type" error={errors.type?.[0]}>
                    <Select value={form.type} onChange={(event) => setForm({ ...form, type: event.target.value as CategoryType })}>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </Select>
                </Field>
                <Field label="Colour" error={errors.color?.[0]}>
                    <Input type="color" value={form.color} onChange={(event) => setForm({ ...form, color: event.target.value })} className="h-10 p-1" />
                </Field>
            </div>
            <div className="rounded-xl bg-slate-50 p-3">
                <p className="mb-3 text-sm font-medium text-slate-700">Normal amount per month (optional)</p>
                <div className="grid grid-cols-2 gap-4">
                    <Field label="Normal amount" error={errors.normal_amount?.[0]}>
                        <Input type="number" step="0.01" min="0" value={form.normal_amount} onChange={(event) => setForm({ ...form, normal_amount: event.target.value })} placeholder="e.g. 500" />
                    </Field>
                    <Field label="Alert if off by more than (%)" error={errors.normal_tolerance?.[0]}>
                        <Input type="number" min="1" max="200" value={form.normal_tolerance} onChange={(event) => setForm({ ...form, normal_tolerance: event.target.value })} />
                    </Field>
                </div>
                <p className="mt-2 text-xs text-slate-500">
                    You get an alert as soon as the month goes too far over, and after the month ends if it stayed too far under. With AI turned on in Settings, Claude explains why.
                </p>
            </div>
            {message && <p className="text-sm text-red-600">{message}</p>}
            <Button type="submit">{category ? 'Save category' : 'Add category'}</Button>
        </form>
    );
}

function DeleteCategory({ workspaceId, category, others, onDone }: { workspaceId: number; category: Category; others: Category[]; onDone: () => void }) {
    const [target, setTarget] = useState('');
    const [error, setError] = useState<string | null>(null);
    const inUse = (category.transactions_count ?? 0) > 0;

    async function submit(event: FormEvent) {
        event.preventDefault();
        try {
            await api(`workspaces/${workspaceId}/categories/${category.id}`, { method: 'DELETE', body: { reassign_to: Number(target) || null } });
            onDone();
        } catch (caught) {
            setError(caught instanceof ApiError ? (Object.values(caught.errors)[0]?.[0] ?? caught.message) : 'Could not delete.');
        }
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            {inUse ? (
                <Field label={`Move its ${category.transactions_count} transactions to`}>
                    <Select value={target} onChange={(event) => setTarget(event.target.value)} required>
                        <option value="">Choose a category…</option>
                        {others.map((other) => (
                            <option key={other.id} value={other.id}>{other.name}</option>
                        ))}
                    </Select>
                </Field>
            ) : (
                <p className="text-sm text-slate-600">This category has no transactions and can be deleted safely.</p>
            )}
            {error && <ErrorNotice message={error} />}
            <Button type="submit" variant="danger">Delete {category.name}</Button>
        </form>
    );
}

export default function Categories() {
    const workspace = useWorkspace();
    const categories = useApi<{ data: Category[] }>(`workspaces/${workspace.id}/categories`);
    const [editing, setEditing] = useState<Category | null>(null);
    const [creating, setCreating] = useState<CategoryType | null>(null);
    const [deleting, setDeleting] = useState<Category | null>(null);
    const canManage = workspace.permissions.manage_finances;

    async function done() {
        setEditing(null);
        setCreating(null);
        setDeleting(null);
        await categories.reload();
    }

    if (categories.error) {
        return <ErrorNotice message={categories.error} />;
    }
    if (!categories.data) {
        return <Loading />;
    }

    const all = categories.data.data;

    return (
        <div>
            <PageHeader title="Categories" subtitle="Group transactions so reports and budgets make sense." />
            <div className="grid gap-6 md:grid-cols-2">
                {(['expense', 'income'] as const).map((type) => (
                    <Card
                        key={type}
                        title={type === 'expense' ? 'Expense categories' : 'Income categories'}
                        action={canManage && <Button variant="secondary" className="px-3 py-1 text-xs" onClick={() => setCreating(type)}>+ Add</Button>}
                    >
                        <ul className="flex flex-col divide-y divide-slate-100">
                            {all.filter((category) => category.type === type).map((category) => (
                                <li key={category.id} className="flex items-center gap-3 py-2">
                                    <span className="size-3 shrink-0 rounded-full" style={{ background: category.color ?? '#cbd5e1' }} />
                                    <span className="flex min-w-0 flex-1 flex-col">
                                        <span className="truncate text-sm font-medium text-slate-700">{category.name}</span>
                                        <NormLine category={category} currency={workspace.base_currency} />
                                    </span>
                                    <span className="hidden text-xs text-slate-400 sm:inline">{category.transactions_count ?? 0} used</span>
                                    {canManage && (
                                        <>
                                            <Button variant="ghost" className="px-2 py-1 text-xs" onClick={() => setEditing(category)}>Edit</Button>
                                            <Button variant="ghost" className="px-2 py-1 text-xs text-red-600" onClick={() => setDeleting(category)}>Delete</Button>
                                        </>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Card>
                ))}
            </div>

            <Modal title={editing ? 'Edit category' : 'New category'} open={!!editing || !!creating} onClose={() => { setEditing(null); setCreating(null); }}>
                <CategoryForm key={editing?.id ?? creating ?? 'new'} workspaceId={workspace.id} category={editing} defaultType={creating ?? 'expense'} onSaved={done} />
            </Modal>
            <Modal title="Delete category" open={!!deleting} onClose={() => setDeleting(null)}>
                {deleting && (
                    <DeleteCategory
                        workspaceId={workspace.id}
                        category={deleting}
                        others={all.filter((category) => category.type === deleting.type && category.id !== deleting.id)}
                        onDone={done}
                    />
                )}
            </Modal>
        </div>
    );
}

function NormLine({ category, currency }: { category: Category; currency: string }) {
    const total = money(category.month_total ?? '0', currency);

    if (!category.norm || !category.normal_amount) {
        return <span className="text-xs text-slate-400">This month: {total}</span>;
    }

    const { status, deviation_percent: deviation } = category.norm;
    const styles: Record<string, string> = {
        normal: 'bg-brand-50 text-brand-700',
        over: 'bg-red-50 text-red-700',
        under: 'bg-amber-50 text-amber-700',
        below_so_far: 'bg-slate-100 text-slate-600',
    };
    const labels: Record<string, string> = { normal: 'normal', over: 'too high', under: 'too low', below_so_far: 'below so far' };

    return (
        <span className="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
            {total} of normal {money(category.normal_amount, currency)}
            <span className={`rounded-full px-1.5 py-0.5 font-medium ${styles[status]}`}>
                {deviation > 0 ? '+' : ''}{deviation}% · {labels[status]}
            </span>
        </span>
    );
}
