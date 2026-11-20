import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';
import { api } from './api';
import type { User, Workspace } from '../types';

interface WorkspaceState {
    user: User | null;
    workspaces: Workspace[];
    workspace: Workspace | null;
    loading: boolean;
    selectWorkspace: (id: number) => void;
    reload: () => Promise<void>;
    refreshUser: () => Promise<void>;
}

const WorkspaceContext = createContext<WorkspaceState | null>(null);
const STORAGE_KEY = 'accountant.workspace';

function readStoredId(): number | null {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        return value ? Number(value) : null;
    } catch {
        return null;
    }
}

function storeId(id: number): void {
    try {
        window.localStorage.setItem(STORAGE_KEY, String(id));
    } catch {
        // Storage can be unavailable (private mode); the selection then lasts for this visit only.
    }
}

export function WorkspaceProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<User | null>(null);
    const [workspaces, setWorkspaces] = useState<Workspace[]>([]);
    const [currentId, setCurrentId] = useState<number | null>(readStoredId());
    const [loading, setLoading] = useState(true);

    const refreshUser = useCallback(async () => {
        const me = await api<{ data: User }>('me');
        setUser(me.data);
    }, []);

    const reload = useCallback(async () => {
        const [me, list] = await Promise.all([
            api<{ data: User }>('me'),
            api<{ data: Workspace[] }>('workspaces'),
        ]);
        setUser(me.data);
        setWorkspaces(list.data);
        setLoading(false);
    }, []);

    useEffect(() => {
        void reload();
    }, [reload]);

    const selectWorkspace = useCallback((id: number) => {
        storeId(id);
        setCurrentId(id);
    }, []);

    const workspace = workspaces.find((item) => item.id === currentId) ?? workspaces[0] ?? null;

    return (
        <WorkspaceContext.Provider value={{ user, workspaces, workspace, loading, selectWorkspace, reload, refreshUser }}>
            {children}
        </WorkspaceContext.Provider>
    );
}

export function useWorkspaceState(): WorkspaceState {
    const context = useContext(WorkspaceContext);
    if (!context) {
        throw new Error('useWorkspaceState must be used inside WorkspaceProvider');
    }
    return context;
}

/** The selected workspace; only use inside screens rendered after a workspace exists. */
export function useWorkspace(): Workspace {
    const { workspace } = useWorkspaceState();
    if (!workspace) {
        throw new Error('No workspace selected');
    }
    return workspace;
}
