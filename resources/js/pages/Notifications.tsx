import { Link } from 'react-router';
import { Button, EmptyState, ErrorNotice, Loading, PageHeader, cx } from '../components/ui';
import { api } from '../lib/api';
import { useApi } from '../lib/useApi';
import { useWorkspaceState } from '../lib/workspace';
import type { AppNotification } from '../types';

export default function Notifications() {
    const { refreshUser, workspaces, selectWorkspace } = useWorkspaceState();
    const notifications = useApi<{ data: AppNotification[] }>('notifications');

    async function markRead(notification: AppNotification) {
        if (notification.data.workspace_id && workspaces.some((item) => item.id === notification.data.workspace_id)) {
            selectWorkspace(notification.data.workspace_id);
        }
        if (!notification.read_at) {
            await api(`notifications/${notification.id}`, { method: 'PATCH' });
            await refreshUser();
        }
    }

    async function markAllRead() {
        await api('notifications', { method: 'DELETE' });
        await Promise.all([notifications.reload(), refreshUser()]);
    }

    return (
        <div>
            <PageHeader
                title="Alerts"
                subtitle="Budget alerts and other messages about your workspaces."
                actions={notifications.data?.data.some((item) => !item.read_at) && <Button variant="secondary" onClick={markAllRead}>Mark all as read</Button>}
            />
            {notifications.error && <ErrorNotice message={notifications.error} />}
            {!notifications.data && <Loading />}
            {notifications.data?.data.length === 0 && <EmptyState title="No alerts yet" text="When a budget reaches one of your alert levels, it will appear here and in your email." />}
            <ul className="flex flex-col gap-3">
                {notifications.data?.data.map((notification) => (
                    <li key={notification.id} className={cx('rounded-2xl border bg-white p-4 shadow-sm', notification.read_at ? 'border-slate-200' : 'border-brand-500/40 bg-brand-50/30')}>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="font-semibold text-ink">{notification.data.title}</p>
                                <p className="mt-1 text-sm text-slate-600">{notification.data.message}</p>
                                {notification.data.ai_explanation && (
                                    <div className="mt-3 rounded-lg bg-indigo-50 p-3 text-sm text-indigo-900">
                                        <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-indigo-500">Explained by AI · check before acting</p>
                                        {notification.data.ai_explanation}
                                    </div>
                                )}
                                <p className="mt-2 text-xs text-slate-400">{new Date(notification.created_at).toLocaleString()}</p>
                            </div>
                            {notification.data.link && (
                                <Link
                                    to={notification.data.link.replace(/^\/app/, '') || '/'}
                                    onClick={() => void markRead(notification)}
                                    className="shrink-0 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-brand-700 ring-1 ring-slate-200 hover:bg-slate-50"
                                >
                                    Review
                                </Link>
                            )}
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}
