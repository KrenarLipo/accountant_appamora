export function money(amount: string | number, currency: string, options: { signed?: boolean } = {}): string {
    const value = typeof amount === 'string' ? Number(amount) : amount;
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        signDisplay: options.signed ? 'exceptZero' : 'auto',
    }).format(value);
}

export function monthLabel(month: string, style: 'short' | 'long' = 'long'): string {
    const [year, m] = month.split('-').map(Number);
    return new Date(year, m - 1, 1).toLocaleDateString(undefined, {
        month: style,
        year: style === 'long' ? 'numeric' : '2-digit',
    });
}

export function dateLabel(date: string): string {
    const [year, month, day] = date.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export function shiftMonth(month: string, delta: number): string {
    const [year, m] = month.split('-').map(Number);
    const date = new Date(year, m - 1 + delta, 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

export function todayIso(): string {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

export function percentChange(current: string, previous: string): number | null {
    const prev = Number(previous);
    if (prev === 0) {
        return null;
    }
    return ((Number(current) - prev) / Math.abs(prev)) * 100;
}

export const accountTypeLabels: Record<string, string> = {
    cash: 'Cash',
    bank: 'Bank account',
    wallet: 'Wallet',
    credit_card: 'Credit card',
    savings: 'Savings',
};

export const roleLabels: Record<string, string> = {
    owner: 'Owner',
    admin: 'Admin',
    finance_manager: 'Finance manager',
    contributor: 'Contributor',
    viewer: 'Viewer',
};
