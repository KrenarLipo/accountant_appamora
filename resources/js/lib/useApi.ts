import { useCallback, useEffect, useState } from 'react';
import { api, ApiError } from './api';

type Query = Record<string, string | number | null | undefined>;

/** Loads a GET endpoint and reloads whenever the path or query changes. */
export function useApi<T>(path: string | null, query: Query = {}) {
    const [data, setData] = useState<T | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const queryKey = JSON.stringify(query);

    const load = useCallback(async () => {
        if (!path) {
            return;
        }
        setLoading(true);
        try {
            setData(await api<T>(path, { query: JSON.parse(queryKey) as Query }));
            setError(null);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not load data.');
        } finally {
            setLoading(false);
        }
    }, [path, queryKey]);

    useEffect(() => {
        void load();
    }, [load]);

    return { data, error, loading, reload: load };
}
