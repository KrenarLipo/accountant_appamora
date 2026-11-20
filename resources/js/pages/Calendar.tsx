import { useState } from 'react';
import TransactionForm from '../components/TransactionForm';
import { Button, Card, ErrorNotice, Loading, Modal, PageHeader, cx } from '../components/ui';
import { api } from '../lib/api';
import { dateLabel, money, monthLabel, shiftMonth } from '../lib/format';
import { useApi } from '../lib/useApi';
import { useWorkspace, useWorkspaceState } from '../lib/workspace';
import type { AccountBalance, CalendarDay, CalendarMonth, Category, Paginated, Totals, Transaction } from '../types';

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function monthCells(month: string): (string | null)[] {
    const [year, m] = month.split('-').map(Number);
    const first = new Date(year, m - 1, 1);
    const daysInMonth = new Date(year, m, 0).getDate();
    const leading = (first.getDay() + 6) % 7; // Monday first
    const cells: (string | null)[] = Array(leading).fill(null);
    for (let day = 1; day <= daysInMonth; day++) {
        cells.push(`${month}-${String(day).padStart(2, '0')}`);
    }
    while (cells.length % 7 !== 0) {
        cells.push(null);
    }
    return cells;
}

function Result({ totals, currency, size = 'sm' }: { totals: Totals; currency: string; size?: 'sm' | 'lg' }) {
    const net = Number(totals.net);
    return (
        <span className={cx('font-semibold', net > 0 && 'text-brand-700', net < 0 && 'text-red-600', net === 0 && 'text-slate-500', size === 'lg' ? 'text-2xl' : 'text-xs')}>
            {money(net, currency, { signed: true })}
        </span>
    );
}

function DayPanel({ date, currency, summary, onChanged }: { date: string; currency: string; summary: CalendarDay | undefined; onChanged: () => void }) {
    const workspace = useWorkspace();
    const { refreshUser } = useWorkspaceState();
    const base = `workspaces/${workspace.id}`;
    const transactions = useApi<Paginated<Transaction>>(`${base}/transactions`, { from: date, to: date, per_page: 100 });
    const accounts = useApi<{ data: AccountBalance[] }>(`${base}/accounts`);
    const categories = useApi<{ data: Category[] }>(`${base}/categories`);
    const [editing, setEditing] = useState<Transaction | null>(null);
    const [adding, setAdding] = useState(false);
    const canEdit = workspace.permissions.record_transactions;

    async function changed() {
        setEditing(null);
        setAdding(false);
        await Promise.all([transactions.reload(), refreshUser()]);
        onChanged();
    }

    async function remove(transaction: Transaction) {
        if (window.confirm('Delete this transaction?')) {
            await api(`${base}/transactions/${transaction.id}`, { method: 'DELETE' });
            await changed();
        }
    }

    if ((adding || editing) && accounts.data && categories.data) {
        return (
            <div>
                <button type="button" className="mb-4 text-sm font-medium text-brand-700 hover:underline" onClick={() => { setAdding(false); setEditing(null); }}>
                    ← Back to {dateLabel(date)}
                </button>
                <TransactionForm
                    key={editing?.id ?? 'new'}
                    workspaceId={workspace.id}
                    accounts={accounts.data.data}
                    categories={categories.data.data}
                    transaction={editing}
                    defaultDate={date}
                    currency={currency}
                    onSaved={changed}
                />
            </div>
        );
    }

    const totals = summary ?? { income: '0', expenses: '0', returns: '0', net: '0' };

    return (
        <div className="flex flex-col gap-4">
            <div className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                <div className="rounded-lg bg-slate-50 p-2"><p className="text-xs text-slate-500">Income</p><p className="font-semibold">{money(totals.income, currency)}</p></div>
                <div className="rounded-lg bg-slate-50 p-2"><p className="text-xs text-slate-500">Paid out</p><p className="font-semibold">{money(totals.expenses, currency)}</p></div>
                <div className="rounded-lg bg-slate-50 p-2"><p className="text-xs text-slate-500">Earned back</p><p className="font-semibold">{money(totals.returns, currency)}</p></div>
                <div className="rounded-lg bg-slate-50 p-2"><p className="text-xs text-slate-500">Result</p><Result totals={totals} currency={currency} /></div>
            </div>

            {transactions.loading && !transactions.data && <Loading />}
            {transactions.data?.data.length === 0 && <p className="py-4 text-center text-sm text-slate-400">Nothing recorded on this day.</p>}
            <ul className="flex flex-col divide-y divide-slate-100">
                {transactions.data?.data.map((transaction) => (
                    <li key={transaction.id} className="flex items-center gap-3 py-2">
                        <span className="size-2.5 shrink-0 rounded-full" style={{ background: transaction.category?.color ?? '#cbd5e1' }} />
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-slate-800">
                                {transaction.type === 'transfer'
                                    ? `Transfer → ${transaction.transfer_account?.name}`
                                    : transaction.merchant || transaction.category?.name || 'Uncategorized'}
                            </p>
                            <p className="text-xs text-slate-500">
                                {transaction.type} · {transaction.category?.name ?? '—'}
                                {transaction.profit !== null && (
                                    <span className={Number(transaction.profit) >= 0 ? ' text-brand-700' : ' text-red-600'}>
                                        {' '}· earned back {money(transaction.return_amount!, currency)} ({Number(transaction.profit) >= 0 ? 'profit' : 'loss'} {money(Math.abs(Number(transaction.profit)), currency)})
                                    </span>
                                )}
                            </p>
                        </div>
                        <span className={cx('text-sm font-semibold', transaction.type === 'income' ? 'text-brand-700' : 'text-ink')}>
                            {transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '−' : ''}
                            {money(transaction.amount, currency)}
                        </span>
                        {canEdit && (
                            <div className="flex">
                                <Button variant="ghost" className="px-2 py-1 text-xs" onClick={() => setEditing(transaction)}>Edit</Button>
                                <Button variant="ghost" className="px-2 py-1 text-xs text-red-600" onClick={() => remove(transaction)}>Delete</Button>
                            </div>
                        )}
                    </li>
                ))}
            </ul>
            {canEdit && <Button onClick={() => setAdding(true)}>+ Add to {dateLabel(date)}</Button>}
        </div>
    );
}

export default function Calendar() {
    const workspace = useWorkspace();
    const [month, setMonth] = useState<string | null>(null);
    const [selected, setSelected] = useState<string | null>(null);
    const calendar = useApi<{ data: CalendarMonth }>(`workspaces/${workspace.id}/calendar`, { month });

    if (calendar.error) {
        return <ErrorNotice message={calendar.error} />;
    }
    if (!calendar.data) {
        return <Loading />;
    }

    const data = calendar.data.data;
    const byDate = new Map(data.days.map((day) => [day.date, day]));
    const currency = data.currency;

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Calendar"
                subtitle="Tap a day to see, add or change what you paid and earned."
                actions={
                    <div className="flex items-center gap-1">
                        <Button variant="secondary" onClick={() => setMonth(shiftMonth(data.month, -1))} aria-label="Previous month">‹</Button>
                        <Button variant="secondary" onClick={() => setMonth(null)}>Today</Button>
                        <Button variant="secondary" onClick={() => setMonth(shiftMonth(data.month, 1))} aria-label="Next month">›</Button>
                    </div>
                }
            />

            <div className="grid gap-4 sm:grid-cols-4">
                <Card><p className="text-sm text-slate-500">Income</p><p className="mt-1 text-xl font-bold text-ink">{money(data.totals.income, currency)}</p></Card>
                <Card><p className="text-sm text-slate-500">Paid out</p><p className="mt-1 text-xl font-bold text-ink">{money(data.totals.expenses, currency)}</p></Card>
                <Card><p className="text-sm text-slate-500">Earned back</p><p className="mt-1 text-xl font-bold text-ink">{money(data.totals.returns, currency)}</p></Card>
                <Card>
                    <p className="text-sm text-slate-500">{Number(data.totals.net) >= 0 ? 'Profit' : 'Loss'} in {monthLabel(data.month)}</p>
                    <p className="mt-1"><Result totals={data.totals} currency={currency} size="lg" /></p>
                </Card>
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {weekdays.map((day) => (
                        <div key={day} className="py-2">{day}</div>
                    ))}
                </div>
                <div className="grid grid-cols-7">
                    {monthCells(data.month).map((date, index) => {
                        if (!date) {
                            return <div key={`empty-${index}`} className="min-h-16 border-b border-r border-slate-100 bg-slate-50/50 sm:min-h-24" />;
                        }
                        const day = byDate.get(date);
                        const isToday = date === data.today;
                        return (
                            <button
                                key={date}
                                type="button"
                                onClick={() => setSelected(date)}
                                className="flex min-h-16 flex-col items-start gap-0.5 border-b border-r border-slate-100 p-1.5 text-left hover:bg-brand-50/50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500/40 sm:min-h-24 sm:p-2"
                                aria-label={`${dateLabel(date)}${day ? `, result ${money(day.net, currency, { signed: true })}` : ', nothing recorded'}`}
                            >
                                <span className={cx('grid size-6 place-items-center rounded-full text-xs font-medium', isToday ? 'bg-ink text-white' : 'text-slate-600')}>
                                    {Number(date.slice(-2))}
                                </span>
                                {day && (
                                    <>
                                        <Result totals={day} currency={currency} />
                                        <span className="hidden text-[11px] leading-tight text-slate-400 sm:block">
                                            {Number(day.income) + Number(day.returns) > 0 && <>in {money(Number(day.income) + Number(day.returns), currency)}<br /></>}
                                            {Number(day.expenses) > 0 && <>out {money(day.expenses, currency)}</>}
                                        </span>
                                    </>
                                )}
                            </button>
                        );
                    })}
                </div>
            </div>
            <p className="text-xs text-slate-400">Result = income + money earned back − expenses. Transfers between your own accounts are not counted.</p>

            <Modal title={selected ? dateLabel(selected) : ''} open={!!selected} onClose={() => setSelected(null)}>
                {selected && <DayPanel key={selected} date={selected} currency={currency} summary={byDate.get(selected)} onChanged={() => void calendar.reload()} />}
            </Modal>
        </div>
    );
}
