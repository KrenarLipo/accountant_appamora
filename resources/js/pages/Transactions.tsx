import { useState } from 'react';
import { useSearchParams } from 'react-router';
import TransactionForm from '../components/TransactionForm';
import { Button, Card, EmptyState, ErrorNotice, Input, Loading, Modal, PageHeader, Select } from '../components/ui';
import { api } from '../lib/api';
import { dateLabel, money } from '../lib/format';
import { useApi } from '../lib/useApi';
import { useWorkspace, useWorkspaceState } from '../lib/workspace';
import type { AccountBalance, Category, Paginated, Transaction } from '../types';

export default function Transactions() {
    const workspace = useWorkspace();
    const { refreshUser } = useWorkspaceState();
    const [searchParams, setSearchParams] = useSearchParams();
    const [filters, setFilters] = useState({ from: '', to: '', type: '', account_id: '', category_id: '', search: '' });
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<Transaction | null>(null);
    const [creating, setCreating] = useState(searchParams.get('new') === '1');

    const base = `workspaces/${workspace.id}`;
    const transactions = useApi<Paginated<Transaction>>(`${base}/transactions`, { ...filters, page });
    const accounts = useApi<{ data: AccountBalance[] }>(`${base}/accounts`);
    const categories = useApi<{ data: Category[] }>(`${base}/categories`);
    const canEdit = workspace.permissions.record_transactions;

    const setFilter = (key: keyof typeof filters) => (event: { target: { value: string } }) => {
        setPage(1);
        setFilters({ ...filters, [key]: event.target.value });
    };

    function closeForm() {
        setCreating(false);
        setEditing(null);
        if (searchParams.has('new')) {
            setSearchParams({});
        }
    }

    async function saved() {
        closeForm();
        await Promise.all([transactions.reload(), accounts.reload(), refreshUser()]);
    }

    async function remove(transaction: Transaction) {
        if (!window.confirm('Delete this transaction? It will be removed from all totals.')) {
            return;
        }
        await api(`${base}/transactions/${transaction.id}`, { method: 'DELETE' });
        await transactions.reload();
    }

    const signed = (transaction: Transaction) => {
        if (transaction.type === 'transfer') {
            return <span className="text-slate-500">{money(transaction.amount, transaction.currency)}</span>;
        }
        const income = transaction.type === 'income';
        return (
            <span className={income ? 'text-brand-700' : 'text-ink'}>
                {income ? '+' : '−'}
                {money(transaction.amount, transaction.currency)}
            </span>
        );
    };

    const formReady = accounts.data && categories.data;

    return (
        <div>
            <PageHeader
                title="Transactions"
                subtitle={transactions.data ? `${transactions.data.meta.total} transactions` : undefined}
                actions={canEdit && <Button onClick={() => setCreating(true)}>+ Add transaction</Button>}
            />

            <Card className="mb-4">
                <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <Input placeholder="Search merchant or note" value={filters.search} onChange={setFilter('search')} className="sm:col-span-3 lg:col-span-2" aria-label="Search" />
                    <Input type="date" value={filters.from} onChange={setFilter('from')} aria-label="From date" />
                    <Input type="date" value={filters.to} onChange={setFilter('to')} aria-label="To date" />
                    <Select value={filters.type} onChange={setFilter('type')} aria-label="Type">
                        <option value="">All types</option>
                        <option value="expense">Expenses</option>
                        <option value="income">Income</option>
                        <option value="transfer">Transfers</option>
                    </Select>
                    <Select value={filters.account_id} onChange={setFilter('account_id')} aria-label="Account">
                        <option value="">All accounts</option>
                        {accounts.data?.data.map((account) => (
                            <option key={account.id} value={account.id}>{account.name}</option>
                        ))}
                    </Select>
                    <Select value={filters.category_id} onChange={setFilter('category_id')} aria-label="Category" className="lg:col-start-6">
                        <option value="">All categories</option>
                        {categories.data?.data.map((category) => (
                            <option key={category.id} value={category.id}>{category.name}</option>
                        ))}
                    </Select>
                </div>
            </Card>

            {transactions.error && <ErrorNotice message={transactions.error} />}
            {!transactions.data && transactions.loading && <Loading />}

            {transactions.data && transactions.data.data.length === 0 && (
                <EmptyState
                    title="No transactions found"
                    text="Add income or expenses, or change the filters above."
                    action={canEdit && <Button onClick={() => setCreating(true)}>Add transaction</Button>}
                />
            )}

            {transactions.data && transactions.data.data.length > 0 && (
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <ul className="divide-y divide-slate-100">
                        {transactions.data.data.map((transaction) => (
                            <li key={transaction.id} className="flex items-center gap-3 px-4 py-3 hover:bg-slate-50">
                                <span
                                    className="size-2.5 shrink-0 rounded-full"
                                    style={{ background: transaction.category?.color ?? (transaction.type === 'transfer' ? '#94a3b8' : '#cbd5e1') }}
                                    aria-hidden="true"
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium text-slate-800">
                                        {transaction.type === 'transfer'
                                            ? `Transfer: ${transaction.account.name} → ${transaction.transfer_account?.name}`
                                            : transaction.merchant || transaction.category?.name || 'Uncategorized'}
                                    </p>
                                    <p className="truncate text-xs text-slate-500">
                                        {dateLabel(transaction.transaction_date)}
                                        {transaction.type !== 'transfer' && ` · ${transaction.category?.name ?? 'Uncategorized'} · ${transaction.account.name}`}
                                        {transaction.description && ` · ${transaction.description}`}
                                    </p>
                                </div>
                                <span className="flex flex-col items-end text-sm font-semibold whitespace-nowrap">
                                    {signed(transaction)}
                                    {transaction.profit !== null && (
                                        <span className={`text-xs font-medium ${Number(transaction.profit) >= 0 ? 'text-brand-700' : 'text-red-600'}`}>
                                            {Number(transaction.profit) >= 0 ? 'Profit' : 'Loss'} {money(Math.abs(Number(transaction.profit)), transaction.currency)}
                                        </span>
                                    )}
                                </span>
                                {canEdit && (
                                    <div className="flex gap-1">
                                        <Button variant="ghost" className="px-2 py-1 text-xs" onClick={() => setEditing(transaction)}>Edit</Button>
                                        <Button variant="ghost" className="px-2 py-1 text-xs text-red-600" onClick={() => remove(transaction)}>Delete</Button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                    {transactions.data.meta.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm">
                            <Button variant="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Previous</Button>
                            <span className="text-slate-500">Page {transactions.data.meta.current_page} of {transactions.data.meta.last_page}</span>
                            <Button variant="secondary" disabled={page >= transactions.data.meta.last_page} onClick={() => setPage(page + 1)}>Next</Button>
                        </div>
                    )}
                </div>
            )}

            <Modal title={editing ? 'Edit transaction' : 'Add transaction'} open={(creating || !!editing) && !!formReady} onClose={closeForm}>
                {formReady && (
                    <TransactionForm
                        key={editing?.id ?? 'new'}
                        workspaceId={workspace.id}
                        accounts={accounts.data!.data}
                        categories={categories.data!.data}
                        transaction={editing}
                        currency={workspace.base_currency}
                        onSaved={saved}
                    />
                )}
            </Modal>
        </div>
    );
}
