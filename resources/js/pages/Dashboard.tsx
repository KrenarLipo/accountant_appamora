import { useState } from 'react';
import { Link } from 'react-router';
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Button, Card, EmptyState, ErrorNotice, Loading, PageHeader, ProgressBar } from '../components/ui';
import { accountTypeLabels, money, monthLabel, percentChange, shiftMonth } from '../lib/format';
import { useApi } from '../lib/useApi';
import { useWorkspace } from '../lib/workspace';
import type { Dashboard as DashboardData } from '../types';

const fallbackColors = ['#10b981', '#2563eb', '#f59e0b', '#db2777', '#7c3aed', '#0891b2', '#64748b'];

function Change({ current, previous, inverse = false }: { current: string; previous: string; inverse?: boolean }) {
    const change = percentChange(current, previous);
    if (change === null) {
        return <span className="text-xs text-slate-400">No data for last month</span>;
    }
    const good = inverse ? change <= 0 : change >= 0;
    return (
        <span className={`text-xs font-medium ${good ? 'text-brand-700' : 'text-red-600'}`}>
            {change >= 0 ? '▲' : '▼'} {Math.abs(change).toFixed(0)}% vs last month
        </span>
    );
}

export default function Dashboard() {
    const workspace = useWorkspace();
    const [month, setMonth] = useState<string | null>(null);
    const { data, error, loading } = useApi<{ data: DashboardData }>(`workspaces/${workspace.id}/dashboard`, { month });

    if (error) {
        return <ErrorNotice message={error} />;
    }
    if (!data && loading) {
        return <Loading />;
    }
    if (!data) {
        return null;
    }

    const dashboard = data.data;
    const currency = dashboard.currency;
    const fmt = (value: string | number) => money(value, currency);
    const current = dashboard.period.month;

    const trend = dashboard.trend.map((row) => ({
        month: monthLabel(row.month, 'short'),
        Income: Number(row.income),
        Expenses: Number(row.expenses),
    }));
    const categories = dashboard.expenses_by_category.map((row, index) => ({
        ...row,
        value: Number(row.total),
        fill: row.color ?? fallbackColors[index % fallbackColors.length],
    }));

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Dashboard"
                subtitle={`${monthLabel(current)} · cash-basis view in ${currency}`}
                actions={
                    <div className="flex items-center gap-1">
                        <Button variant="secondary" onClick={() => setMonth(shiftMonth(current, -1))} aria-label="Previous month">‹</Button>
                        <Button variant="secondary" onClick={() => setMonth(null)}>This month</Button>
                        <Button variant="secondary" onClick={() => setMonth(shiftMonth(current, 1))} aria-label="Next month">›</Button>
                    </div>
                }
            />

            {dashboard.transaction_count === 0 && (
                <EmptyState
                    title="Add your first transaction"
                    text="Your dashboard fills in as you record income and expenses. It only takes a few seconds."
                    action={<Link to="/transactions?new=1" className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Add transaction</Link>}
                />
            )}

            <div className="grid gap-4 sm:grid-cols-3">
                <Card>
                    <p className="text-sm text-slate-500">Income</p>
                    <p className="mt-1 text-2xl font-bold text-ink">{fmt(dashboard.totals.income)}</p>
                    <Change current={dashboard.totals.income} previous={dashboard.previous_totals.income} />
                </Card>
                <Card>
                    <p className="text-sm text-slate-500">Expenses</p>
                    <p className="mt-1 text-2xl font-bold text-ink">{fmt(dashboard.totals.expenses)}</p>
                    <Change current={dashboard.totals.expenses} previous={dashboard.previous_totals.expenses} inverse />
                </Card>
                <Card className={Number(dashboard.totals.net) >= 0 ? 'bg-brand-50/50' : 'bg-red-50/50'}>
                    <p className="text-sm text-slate-500">Net movement</p>
                    <p className={`mt-1 text-2xl font-bold ${Number(dashboard.totals.net) >= 0 ? 'text-brand-700' : 'text-red-600'}`}>
                        {money(dashboard.totals.net, currency, { signed: true })}
                    </p>
                    <span className="text-xs text-slate-400">Income minus expenses; transfers excluded</span>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card title="Last 6 months" className="lg:col-span-2">
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={trend} margin={{ left: 8, right: 8 }}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                <XAxis dataKey="month" tickLine={false} axisLine={false} fontSize={12} />
                                <YAxis tickLine={false} axisLine={false} fontSize={12} width={70} tickFormatter={(value: number) => fmt(value)} />
                                <Tooltip formatter={(value) => fmt(Number(value))} cursor={{ fill: '#f1f5f9' }} />
                                <Legend iconType="circle" wrapperStyle={{ fontSize: 12 }} />
                                <Bar dataKey="Income" fill="#10b981" radius={[4, 4, 0, 0]} />
                                <Bar dataKey="Expenses" fill="#94a3b8" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </Card>

                <Card title="Spending by category">
                    {categories.length === 0 ? (
                        <p className="py-10 text-center text-sm text-slate-400">No expenses this month.</p>
                    ) : (
                        <>
                            <div className="h-40">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={categories} dataKey="value" nameKey="name" innerRadius={45} outerRadius={70} paddingAngle={2}>
                                            {categories.map((row) => (
                                                <Cell key={row.name} fill={row.fill} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value) => fmt(Number(value))} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <ul className="mt-3 flex flex-col gap-1.5 text-sm">
                                {categories.slice(0, 6).map((row) => (
                                    <li key={row.name} className="flex items-center justify-between gap-2">
                                        <span className="flex items-center gap-2 truncate">
                                            <span className="size-2.5 shrink-0 rounded-full" style={{ background: row.fill }} />
                                            {row.name}
                                        </span>
                                        <span className="font-medium text-ink">{fmt(row.total)}</span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card title="Budgets" action={<Link to="/budgets" className="text-sm font-medium text-brand-700 hover:underline">Manage</Link>}>
                    {dashboard.budgets.length === 0 ? (
                        <p className="text-sm text-slate-400">No budgets yet. Set one to get alerts before you overspend.</p>
                    ) : (
                        <ul className="flex flex-col gap-4">
                            {dashboard.budgets.map((budget) => (
                                <li key={budget.id}>
                                    <div className="mb-1 flex justify-between text-sm">
                                        <span className="font-medium text-slate-700">{budget.category_name}</span>
                                        <span className="text-slate-500">{budget.percent_used}%</span>
                                    </div>
                                    <ProgressBar percent={budget.percent_used} />
                                    <p className="mt-1 text-xs text-slate-400">
                                        {fmt(budget.spent)} of {fmt(budget.amount)}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card title="Accounts" action={<Link to="/accounts" className="text-sm font-medium text-brand-700 hover:underline">Manage</Link>}>
                    <ul className="flex flex-col divide-y divide-slate-100">
                        {dashboard.accounts.map((account) => (
                            <li key={account.id} className="flex items-center justify-between py-2 text-sm">
                                <span>
                                    <span className="block font-medium text-slate-700">{account.name}</span>
                                    <span className="text-xs text-slate-400">{accountTypeLabels[account.type]}</span>
                                </span>
                                <span className={`font-semibold ${Number(account.balance) < 0 ? 'text-red-600' : 'text-ink'}`}>{fmt(account.balance)}</span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-2 text-xs text-slate-400">Balances as of today, across all dates.</p>
                </Card>

                <Card title="Top merchants">
                    {dashboard.top_merchants.length === 0 ? (
                        <p className="text-sm text-slate-400">Add a merchant to expenses to see where money goes.</p>
                    ) : (
                        <ul className="flex flex-col divide-y divide-slate-100">
                            {dashboard.top_merchants.map((row) => (
                                <li key={row.merchant} className="flex items-center justify-between py-2 text-sm">
                                    <span className="truncate">
                                        {row.merchant} <span className="text-xs text-slate-400">×{row.count}</span>
                                    </span>
                                    <span className="font-medium text-ink">{fmt(row.total)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </div>
    );
}
