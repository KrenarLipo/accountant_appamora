export type Role = 'owner' | 'admin' | 'finance_manager' | 'contributor' | 'viewer';
export type TransactionType = 'income' | 'expense' | 'transfer';
export type CategoryType = 'income' | 'expense';
export type AccountType = 'cash' | 'bank' | 'wallet' | 'credit_card' | 'savings';

export interface User {
    id: number;
    name: string;
    email: string;
    unread_notifications: number;
}

export interface Workspace {
    id: number;
    name: string;
    type: 'personal' | 'business';
    base_currency: string;
    timezone: string;
    country: string | null;
    role: Role;
    permissions: {
        manage_workspace: boolean;
        manage_finances: boolean;
        record_transactions: boolean;
        delete_workspace: boolean;
    };
}

export interface AccountBalance {
    id: number;
    name: string;
    type: AccountType;
    currency: string;
    is_active: boolean;
    opening_balance: string;
    balance: string;
}

export interface Category {
    id: number;
    name: string;
    type: CategoryType;
    color: string | null;
    parent_id: number | null;
    transactions_count?: number;
}

export interface Transaction {
    id: number;
    type: TransactionType;
    amount: string;
    currency: string;
    transaction_date: string;
    merchant: string | null;
    description: string | null;
    account: { id: number; name: string };
    transfer_account: { id: number; name: string } | null;
    category: { id: number; name: string; color: string | null } | null;
    created_by: string | null;
}

export interface BudgetStatus {
    id: number;
    category_id: number | null;
    category_name: string;
    amount: string;
    spent: string;
    remaining: string;
    percent_used: number;
    alert_thresholds: number[];
}

export interface Totals {
    income: string;
    expenses: string;
    net: string;
}

export interface Dashboard {
    period: { month: string; from: string; to: string };
    currency: string;
    totals: Totals;
    previous_totals: Totals;
    accounts: AccountBalance[];
    trend: { month: string; income: string; expenses: string }[];
    expenses_by_category: { category_id: number | null; name: string; color: string | null; total: string }[];
    top_merchants: { merchant: string; total: string; count: number }[];
    budgets: BudgetStatus[];
    transaction_count: number;
}

export interface AppNotification {
    id: string;
    data: { title: string; message: string; link?: string; workspace_id?: number };
    read_at: string | null;
    created_at: string;
}

export interface Paginated<T> {
    data: T[];
    meta: { current_page: number; last_page: number; total: number; per_page: number };
}
