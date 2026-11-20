import WorkspaceForm from '../components/WorkspaceForm';
import { useWorkspaceState } from '../lib/workspace';

export default function Onboarding() {
    const { user, reload, selectWorkspace } = useWorkspaceState();

    return (
        <div className="flex min-h-full items-start justify-center px-4 py-12">
            <div className="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p className="text-sm font-medium text-brand-700">Welcome{user ? `, ${user.name.split(' ')[0]}` : ''}!</p>
                <h1 className="mt-1 text-2xl font-bold text-ink">Set up your first workspace</h1>
                <p className="mt-2 text-sm text-slate-500">
                    A workspace holds your accounts, categories and budgets. We will add starter categories you can change any time.
                </p>
                <div className="mt-6">
                    <WorkspaceForm
                        submitLabel="Create and open dashboard"
                        onCreated={async (workspace) => {
                            selectWorkspace(workspace.id);
                            await reload();
                        }}
                    />
                </div>
            </div>
        </div>
    );
}
