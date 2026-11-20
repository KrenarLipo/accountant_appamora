import { useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router';
import { api } from '../lib/api';
import { useWorkspaceState } from '../lib/workspace';
import { cx } from './ui';

const links = [
    { to: '/', label: 'Dashboard', end: true },
    { to: '/calendar', label: 'Calendar' },
    { to: '/transactions', label: 'Transactions' },
    { to: '/budgets', label: 'Budgets' },
    { to: '/accounts', label: 'Accounts' },
    { to: '/categories', label: 'Categories' },
    { to: '/import-export', label: 'Import & export' },
    { to: '/settings', label: 'Settings' },
];

async function logout() {
    await api('/logout', { method: 'POST' }).catch(() => undefined);
    window.location.href = '/';
}

export default function Layout() {
    const { user, workspaces, workspace, selectWorkspace } = useWorkspaceState();
    const [menuOpen, setMenuOpen] = useState(false);
    const navigate = useNavigate();

    const nav = (
        <nav className="flex flex-col gap-1">
            {links.map((link) => (
                <NavLink
                    key={link.to}
                    to={link.to}
                    end={link.end}
                    onClick={() => setMenuOpen(false)}
                    className={({ isActive }) =>
                        cx('rounded-lg px-3 py-2 text-sm font-medium', isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white')
                    }
                >
                    {link.label}
                </NavLink>
            ))}
        </nav>
    );

    const switcher = (
        <div className="flex flex-col gap-1">
            <label htmlFor="workspace-switcher" className="text-xs font-medium uppercase tracking-wide text-slate-400">
                Workspace
            </label>
            <select
                id="workspace-switcher"
                value={workspace?.id ?? ''}
                onChange={(event) => {
                    if (event.target.value === 'new') {
                        navigate('/settings?new=1');
                        return;
                    }
                    selectWorkspace(Number(event.target.value));
                    navigate('/');
                }}
                className="w-full rounded-lg border border-white/10 bg-white/5 px-2 py-2 text-sm text-white outline-none focus:ring-2 focus:ring-brand-500/50"
            >
                {workspaces.map((item) => (
                    <option key={item.id} value={item.id} className="text-slate-900">
                        {item.name} ({item.type === 'business' ? 'Business' : 'Personal'})
                    </option>
                ))}
                <option value="new" className="text-slate-900">+ New workspace…</option>
            </select>
        </div>
    );

    return (
        <div className="flex min-h-full">
            <aside className="hidden w-64 shrink-0 flex-col gap-6 bg-ink p-5 lg:flex">
                <a href="/" className="flex items-center gap-2 font-semibold text-white">
                    <span className="grid size-8 place-items-center rounded-lg bg-brand-500 text-sm font-bold">A</span>
                    Accountant
                </a>
                {switcher}
                {nav}
                <div className="mt-auto flex flex-col gap-2 border-t border-white/10 pt-4 text-sm text-slate-300">
                    <span className="truncate">{user?.name}</span>
                    <button type="button" onClick={logout} className="text-left text-slate-400 hover:text-white">
                        Log out
                    </button>
                </div>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:px-8">
                    <button type="button" className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" onClick={() => setMenuOpen(!menuOpen)} aria-label="Menu" aria-expanded={menuOpen}>
                        ☰
                    </button>
                    <p className="truncate text-sm text-slate-500">
                        {workspace?.name} · <span className="font-medium text-slate-700">{workspace?.base_currency}</span>
                    </p>
                    <NavLink to="/notifications" className="relative rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                        Alerts
                        {!!user?.unread_notifications && (
                            <span className="ml-1.5 rounded-full bg-red-500 px-1.5 py-0.5 text-xs font-semibold text-white">{user.unread_notifications}</span>
                        )}
                    </NavLink>
                </header>

                {menuOpen && (
                    <div className="flex flex-col gap-5 bg-ink p-4 lg:hidden">
                        {switcher}
                        {nav}
                        <button type="button" onClick={logout} className="text-left text-sm text-slate-400">
                            Log out
                        </button>
                    </div>
                )}

                <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-6 lg:px-8 lg:py-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
