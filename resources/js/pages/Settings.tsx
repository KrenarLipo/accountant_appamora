import { useState, type FormEvent } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import WorkspaceForm from '../components/WorkspaceForm';
import { Button, Card, Field, Input, PageHeader } from '../components/ui';
import { api, ApiError } from '../lib/api';
import { roleLabels } from '../lib/format';
import { useWorkspace, useWorkspaceState } from '../lib/workspace';

export default function Settings() {
    const workspace = useWorkspace();
    const { reload, selectWorkspace, user } = useWorkspaceState();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const [form, setForm] = useState({ name: workspace.name, timezone: workspace.timezone, country: workspace.country ?? '' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saved, setSaved] = useState(false);
    const [confirmName, setConfirmName] = useState('');

    async function save(event: FormEvent) {
        event.preventDefault();
        setSaved(false);
        try {
            await api(`workspaces/${workspace.id}`, { method: 'PUT', body: { ...form, country: form.country || null } });
            await reload();
            setErrors({});
            setSaved(true);
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
            }
        }
    }

    async function destroy() {
        await api(`workspaces/${workspace.id}`, { method: 'DELETE' });
        await reload();
        navigate('/');
    }

    return (
        <div className="flex flex-col gap-6">
            <PageHeader title="Settings" subtitle={`Signed in as ${user?.email}`} />

            <Card title="This workspace">
                <p className="mb-4 text-sm text-slate-500">
                    {workspace.type === 'business' ? 'Business' : 'Personal'} workspace · currency {workspace.base_currency} · your role:{' '}
                    <span className="font-medium text-slate-700">{roleLabels[workspace.role]}</span>
                </p>
                {workspace.permissions.manage_workspace ? (
                    <form onSubmit={save} className="grid gap-4 sm:grid-cols-3">
                        <Field label="Name" error={errors.name?.[0]}>
                            <Input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required />
                        </Field>
                        <Field label="Time zone" error={errors.timezone?.[0]}>
                            <Input value={form.timezone} onChange={(event) => setForm({ ...form, timezone: event.target.value })} required />
                        </Field>
                        <Field label="Country code" error={errors.country?.[0]} hint="Two letters, e.g. AL">
                            <Input value={form.country} maxLength={2} onChange={(event) => setForm({ ...form, country: event.target.value.toUpperCase() })} />
                        </Field>
                        <div className="flex items-center gap-3 sm:col-span-3">
                            <Button type="submit">Save changes</Button>
                            {saved && <span className="text-sm text-brand-700">Saved.</span>}
                        </div>
                    </form>
                ) : (
                    <p className="text-sm text-slate-500">Only owners and admins can change workspace settings.</p>
                )}
            </Card>

            {workspace.permissions.manage_workspace && <AiCard />}

            <Card title="Create another workspace">
                <p className="mb-4 text-sm text-slate-500">Keep personal and business money apart. You can switch from the menu at any time.</p>
                <div className={searchParams.get('new') === '1' ? 'rounded-xl ring-2 ring-brand-500/40 p-4' : ''}>
                    <WorkspaceForm
                        onCreated={async (created) => {
                            selectWorkspace(created.id);
                            await reload();
                            navigate('/');
                        }}
                    />
                </div>
            </Card>

            {workspace.permissions.delete_workspace && (
                <Card title="Delete workspace" className="border-red-200">
                    <p className="text-sm text-slate-600">
                        This removes <strong>{workspace.name}</strong> and hides all its data from every member. Type the workspace name to confirm.
                    </p>
                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <Input value={confirmName} onChange={(event) => setConfirmName(event.target.value)} placeholder={workspace.name} aria-label="Workspace name" />
                        <Button variant="danger" disabled={confirmName !== workspace.name} onClick={destroy}>Delete workspace</Button>
                    </div>
                </Card>
            )}
        </div>
    );
}

function AiCard() {
    const workspace = useWorkspace();
    const { reload } = useWorkspaceState();
    const [saving, setSaving] = useState(false);

    async function toggle(enabled: boolean) {
        setSaving(true);
        try {
            await api(`workspaces/${workspace.id}`, {
                method: 'PUT',
                body: { name: workspace.name, timezone: workspace.timezone, country: workspace.country, ai_insights_enabled: enabled },
            });
            await reload();
        } finally {
            setSaving(false);
        }
    }

    return (
        <Card title="AI explanations (Claude)">
            <p className="text-sm text-slate-600">
                When a category goes too far above or below its normal amount, Accountant always sends you an alert with the exact figures.
                With this on, Claude (by Anthropic) also writes a short explanation of why and what to check.
            </p>
            <p className="mt-2 text-xs text-slate-500">
                What is sent: the category name, its monthly totals, your normal amount, and up to five of that month&apos;s largest payments (date, amount, merchant).
                No names of people, account numbers or logins. All figures are calculated by Accountant, never by the AI. Not financial or tax advice.
            </p>
            {!workspace.ai_available && (
                <p className="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">The AI service is not configured on this server yet. Alerts still work with standard wording.</p>
            )}
            <label className="mt-4 flex items-center gap-3 text-sm font-medium">
                <input type="checkbox" className="size-4" checked={workspace.ai_insights_enabled} disabled={saving} onChange={(event) => void toggle(event.target.checked)} />
                Let Claude explain alerts for {workspace.name}
            </label>
        </Card>
    );
}
