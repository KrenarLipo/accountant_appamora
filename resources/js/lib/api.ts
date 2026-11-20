export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

function xsrfToken(): string {
    const match = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return match ? decodeURIComponent(match.split('=')[1]) : '';
}

type Query = Record<string, string | number | null | undefined>;

export async function api<T = unknown>(
    path: string,
    options: { method?: string; body?: unknown; query?: Query } = {},
): Promise<T> {
    const url = new URL(path.startsWith('/') ? path : `/api/v1/${path}`, window.location.origin);
    Object.entries(options.query ?? {}).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, String(value));
        }
    });

    const response = await fetch(url, {
        method: options.method ?? 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: options.body === undefined ? undefined : JSON.stringify(options.body),
    });

    if (response.status === 401 || response.status === 419) {
        window.location.href = '/login';
        throw new ApiError('Your session has expired.', response.status);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(payload.message ?? 'Something went wrong.', response.status, payload.errors ?? {});
    }

    return payload as T;
}
