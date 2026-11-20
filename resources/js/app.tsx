import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router';
import Layout from './components/Layout';
import { Loading } from './components/ui';
import { WorkspaceProvider, useWorkspaceState } from './lib/workspace';
import Accounts from './pages/Accounts';
import Budgets from './pages/Budgets';
import Categories from './pages/Categories';
import Dashboard from './pages/Dashboard';
import Notifications from './pages/Notifications';
import Onboarding from './pages/Onboarding';
import Settings from './pages/Settings';
import Transactions from './pages/Transactions';

function AppRoutes() {
    const { loading, workspace } = useWorkspaceState();

    if (loading) {
        return <Loading />;
    }

    if (!workspace) {
        return <Onboarding />;
    }

    return (
        <Routes>
            <Route element={<Layout />}>
                {/* key resets each screen when the workspace changes */}
                <Route index element={<Dashboard key={workspace.id} />} />
                <Route path="transactions" element={<Transactions key={workspace.id} />} />
                <Route path="budgets" element={<Budgets key={workspace.id} />} />
                <Route path="accounts" element={<Accounts key={workspace.id} />} />
                <Route path="categories" element={<Categories key={workspace.id} />} />
                <Route path="notifications" element={<Notifications />} />
                <Route path="settings" element={<Settings key={workspace.id} />} />
                <Route path="*" element={<Navigate to="/" replace />} />
            </Route>
        </Routes>
    );
}

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <BrowserRouter basename="/app">
            <WorkspaceProvider>
                <AppRoutes />
            </WorkspaceProvider>
        </BrowserRouter>
    </StrictMode>,
);
