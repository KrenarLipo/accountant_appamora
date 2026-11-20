import { useEffect, useRef, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes } from 'react';

const cx = (...classes: (string | false | null | undefined)[]) => classes.filter(Boolean).join(' ');

type ButtonVariant = 'primary' | 'accent' | 'secondary' | 'danger' | 'ghost';

export function Button({ variant = 'primary', className, ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: ButtonVariant }) {
    return (
        <button
            type="button"
            className={cx(
                'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-50',
                variant === 'primary' && 'bg-ink text-white shadow-sm hover:bg-ink/90',
                variant === 'accent' && 'bg-brand-500 font-semibold text-ink shadow-sm hover:bg-brand-400',
                variant === 'secondary' && 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
                variant === 'danger' && 'bg-red-600 text-white hover:bg-red-700',
                variant === 'ghost' && 'text-slate-600 hover:bg-slate-100',
                className,
            )}
            {...props}
        />
    );
}

export function Field({ label, error, hint, children }: { label: string; error?: string; hint?: string; children: ReactNode }) {
    return (
        <label className="flex flex-col gap-1.5 text-sm">
            <span className="font-medium text-slate-700">{label}</span>
            {children}
            {hint && !error && <span className="text-xs text-slate-500">{hint}</span>}
            {error && <span className="text-red-600">{error}</span>}
        </label>
    );
}

const controlClass =
    'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30';

export function Input(props: InputHTMLAttributes<HTMLInputElement>) {
    return <input {...props} className={cx(controlClass, props.className)} />;
}

export function Select(props: SelectHTMLAttributes<HTMLSelectElement>) {
    return <select {...props} className={cx(controlClass, props.className)} />;
}

export function Card({ title, action, children, className }: { title?: ReactNode; action?: ReactNode; children: ReactNode; className?: string }) {
    return (
        <section className={cx('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm', className)}>
            {(title || action) && (
                <div className="mb-4 flex items-center justify-between gap-3">
                    {title && <h2 className="font-semibold text-ink">{title}</h2>}
                    {action}
                </div>
            )}
            {children}
        </section>
    );
}

export function PageHeader({ title, subtitle, actions }: { title: string; subtitle?: ReactNode; actions?: ReactNode }) {
    return (
        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 className="text-2xl font-bold text-ink">{title}</h1>
                {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
            </div>
            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
        </div>
    );
}

export function Modal({ title, open, onClose, children }: { title: string; open: boolean; onClose: () => void; children: ReactNode }) {
    const dialog = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const element = dialog.current;
        if (!element) {
            return;
        }
        if (open && !element.open) {
            element.showModal();
        } else if (!open && element.open) {
            element.close();
        }
    }, [open]);

    return (
        <dialog
            ref={dialog}
            onClose={onClose}
            className="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/40"
        >
            {open && (
                <div className="p-6">
                    <div className="mb-5 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-ink">{title}</h2>
                        <button type="button" onClick={onClose} className="rounded p-1 text-slate-400 hover:text-slate-700" aria-label="Close">
                            ✕
                        </button>
                    </div>
                    {children}
                </div>
            )}
        </dialog>
    );
}

export function EmptyState({ title, text, action }: { title: string; text: string; action?: ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p className="font-semibold text-ink">{title}</p>
            <p className="max-w-sm text-sm text-slate-500">{text}</p>
            {action && <div className="mt-3">{action}</div>}
        </div>
    );
}

export function Loading() {
    return <div className="py-16 text-center text-sm text-slate-400">Loading…</div>;
}

export function ErrorNotice({ message }: { message: string }) {
    return <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{message}</div>;
}

export function ProgressBar({ percent }: { percent: number }) {
    const color = percent >= 100 ? 'bg-red-500' : percent >= 90 ? 'bg-orange-500' : percent >= 70 ? 'bg-amber-400' : 'bg-brand-500';
    return (
        <div className="h-2 rounded-full bg-slate-100" role="progressbar" aria-valuenow={Math.round(percent)} aria-valuemin={0} aria-valuemax={100}>
            <div className={cx('h-2 rounded-full transition-all', color)} style={{ width: `${Math.min(percent, 100)}%` }} />
        </div>
    );
}

export { cx };

/** The AppAmora peak badge. `inverted` is for dark backgrounds. */
export function LogoMark({ size = 32, inverted = false }: { size?: number; inverted?: boolean }) {
    return (
        <svg width={size} height={size} viewBox="0 0 64 64" aria-hidden="true">
            <circle cx="32" cy="32" r="31" fill={inverted ? '#FFFFFF' : '#13235B'} />
            <path d="M32 13 L50 49 H41 L32 31 L23 49 H14 Z" fill={inverted ? '#13235B' : '#FFFFFF'} />
            <path d="M32 36 L38.5 49 H25.5 Z" fill="#A6E22E" />
        </svg>
    );
}
