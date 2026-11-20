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
    ai_insights_enabled: boolean;
    ai_available: boolean;
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
    normal_amount: string | null;
    normal_tolerance: number;
    transactions_count?: number;
    month_total?: string;
    norm?: { normal: string; actual: string; tolerance: number; deviation_percent: number; status: 'normal' | 'over' | 'under' | 'below_so_far' } | null;
}

export interface Transaction {
    id: number;
    type: TransactionType;
    amount: string;
    return_amount: string | null;
    profit: string | null;
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
    returns: string;
    net: string;
}

export interface CalendarDay extends Totals {
    date: string;
    count: number;
}

export interface CalendarMonth {
    month: string;
    today: string;
    currency: string;
    days: CalendarDay[];
    totals: Totals;
}

export interface ImportRow {
    line: number;
    status: 'ok' | 'error' | 'duplicate';
    errors: string[];
    date: string | null;
    type: TransactionType | null;
    category: string | null;
    creates_category: boolean;
    amount: string | null;
    return_amount: string | null;
    merchant: string | null;
    note: string | null;
    account: string | null;
    to_account: string | null;
}

export interface ImportPreview {
    rows: ImportRow[];
    summary: { total: number; ready: number; duplicates: number; errors: number; new_categories: string[] };
    missing_columns: string[];
    already_imported_at: string | null;
}

export interface Dashboard {
    period: { month: string; from: string; to: string };
    currency: string;
    totals: Totals;
    previous_totals: Totals;
    accounts: AccountBalance[];
    trend: ({ month: string } & Totals)[];
    expenses_by_category: { category_id: number | null; name: string; color: string | null; total: string }[];
    top_merchants: { merchant: string; total: string; count: number }[];
    budgets: BudgetStatus[];
    transaction_count: number;
}

export interface AppNotification {
    id: string;
    data: { title: string; message: string; link?: string; workspace_id?: number; ai_explanation?: string | null };
    read_at: string | null;
    created_at: string;
}

export interface Paginated<T> {
    data: T[];
    meta: { current_page: number; last_page: number; total: number; per_page: number };
}
