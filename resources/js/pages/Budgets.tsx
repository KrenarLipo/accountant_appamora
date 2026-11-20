import { useState, type FormEvent } from 'react';
import { Button, Card, EmptyState, ErrorNotice, Field, Input, Loading, Modal, PageHeader, ProgressBar, Select } from '../components/ui';
import { api, ApiError } from '../lib/api';
import { money, monthLabel, shiftMonth } from '../lib/format';
import { useApi } from '../lib/useApi';
import { useWorkspace, useWorkspaceState } from '../lib/workspace';
import type { BudgetStatus, Category } from '../types';

const thresholdOptions = [50, 70, 80, 90, 100];

function BudgetForm({ workspaceId, categories, budget, onSaved }: { workspaceId: number; categories: Category[]; budget: BudgetStatus | null; onSaved: () => void }) {
    const [categoryId, setCategoryId] = useState(String(budget?.category_id ?? ''));
    const [amount, setAmount] = useState(budget ? Number(budget.amount).toFixed(2) : '');
    const [thresholds, setThresholds] = useState<number[]>(budget?.alert_thresholds ?? [70, 90, 100]);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        try {
            await api(`workspaces/${workspaceId}/budgets${budget ? `/${budget.id}` : ''}`, {
                method: budget ? 'PUT' : 'POST',
                body: { category_id: Number(categoryId) || null, amount, alert_thresholds: [...thresholds].sort((a, b) => a - b) },
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
            <Field label="What does this budget cover?" error={errors.category_id?.[0]}>
                <Select value={categoryId} onChange={(event) => setCategoryId(event.target.value)}>
                    <option value="">All expenses (overall monthly budget)</option>
                    {categories.filter((category) => category.type === 'expense').map((category) => (
                        <option key={category.id} value={category.id}>{category.name}</option>
                    ))}
                </Select>
            </Field>
            <Field label="Monthly amount" error={errors.amount?.[0]}>
                <Input type="number" step="0.01" min="0.01" value={amount} onChange={(event) => setAmount(event.target.value)} required />
            </Field>
            <fieldset className="flex flex-col gap-2">
                <legend className="mb-1.5 text-sm font-medium text-slate-700">Alert me when I reach</legend>
                <div className="flex flex-wrap gap-2">
                    {thresholdOptions.map((value) => {
                        const checked = thresholds.includes(value);
                        return (
                            <label key={value} className={`cursor-pointer rounded-lg border px-3 py-1.5 text-sm ${checked ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-300'}`}>
                                <input
                                    type="checkbox"
                                    className="sr-only"
                                    checked={checked}
                                    onChange={() => setThresholds(checked ? thresholds.filter((item) => item !== value) : [...thresholds, value])}
                                />
                                {value}%
                            </label>
                        );
                    })}
                </div>
                {errors.alert_thresholds && <span className="text-sm text-red-600">Choose at least one alert level.</span>}
            </fieldset>
            <Button type="submit" disabled={saving}>{saving ? 'Saving…' : budget ? 'Save budget' : 'Create budget'}</Button>
        </form>
    );
}

export default function Budgets() {
    const workspace = useWorkspace();
    const { refreshUser } = useWorkspaceState();
    const [month, setMonth] = useState<string | null>(null);
    const [editing, setEditing] = useState<BudgetStatus | null>(null);
    const [creating, setCreating] = useState(false);
    const base = `workspaces/${workspace.id}`;
    const budgets = useApi<{ data: BudgetStatus[]; meta: { month: string; currency: string } }>(`${base}/budgets`, { month });
    const categories = useApi<{ data: Category[] }>(`${base}/categories`);
    const canManage = workspace.permissions.manage_finances;

    async function saved() {
        setCreating(false);
        setEditing(null);
        await Promise.all([budgets.reload(), refreshUser()]);
    }

    async function remove(budget: BudgetStatus) {
        if (window.confirm(`Delete the ${budget.category_name} budget?`)) {
            await api(`${base}/budgets/${budget.id}`, { method: 'DELETE' });
            await budgets.reload();
        }
    }

    const current = budgets.data?.meta.month;

    return (
        <div>
            <PageHeader
                title="Budgets"
                subtitle={current ? `${monthLabel(current)} · monthly limits with alerts` : undefined}
                actions={
                    <>
                        {current && (
                            <div className="flex items-center gap-1">
                                <Button variant="secondary" onClick={() => setMonth(shiftMonth(current, -1))} aria-label="Previous month">‹</Button>
                                <Button variant="secondary" onClick={() => setMonth(null)}>This month</Button>
                                <Button variant="secondary" onClick={() => setMonth(shiftMonth(current, 1))} aria-label="Next month">›</Button>
                            </div>
                        )}
                        {canManage && <Button onClick={() => setCreating(true)}>+ New budget</Button>}
                    </>
                }
            />

            {budgets.error && <ErrorNotice message={budgets.error} />}
            {!budgets.data && <Loading />}

            {budgets.data?.data.length === 0 && (
                <EmptyState
                    title="No budgets yet"
                    text="Set a monthly limit for everything, or for a category like Groceries. You will get one alert at each level you choose."
                    action={canManage && <Button onClick={() => setCreating(true)}>Create a budget</Button>}
                />
            )}

            <div className="grid gap-4 md:grid-cols-2">
                {budgets.data?.data.map((budget) => {
                    const currency = budgets.data!.meta.currency;
                    const over = Number(budget.remaining) < 0;
                    return (
                        <Card key={budget.id}>
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="font-semibold text-ink">{budget.category_name}</h2>
                                    <p className="text-sm text-slate-500">{money(budget.spent, currency)} of {money(budget.amount, currency)}</p>
                                </div>
                                <span className={`text-lg font-bold ${budget.percent_used >= 100 ? 'text-red-600' : 'text-ink'}`}>{budget.percent_used}%</span>
                            </div>
                            <div className="mt-3"><ProgressBar percent={budget.percent_used} /></div>
                            <div className="mt-3 flex items-center justify-between text-sm">
                                <span className={over ? 'font-medium text-red-600' : 'text-slate-500'}>
                                    {over ? `${money(Math.abs(Number(budget.remaining)), currency)} over budget` : `${money(budget.remaining, currency)} left`}
                                </span>
                                <span className="text-xs text-slate-400">Alerts at {budget.alert_thresholds.join(', ')}%</span>
                            </div>
                            {canManage && (
                                <div className="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                                    <Button variant="ghost" className="px-2 py-1 text-xs" onClick={() => setEditing(budget)}>Edit</Button>
                                    <Button variant="ghost" className="px-2 py-1 text-xs text-red-600" onClick={() => remove(budget)}>Delete</Button>
                                </div>
                            )}
                        </Card>
                    );
                })}
            </div>

            <Modal title={editing ? 'Edit budget' : 'New budget'} open={(creating || !!editing) && !!categories.data} onClose={() => { setCreating(false); setEditing(null); }}>
                {categories.data && (
                    <BudgetForm key={editing?.id ?? 'new'} workspaceId={workspace.id} categories={categories.data.data} budget={editing} onSaved={saved} />
                )}
            </Modal>
        </div>
    );
}
