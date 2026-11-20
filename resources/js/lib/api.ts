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

async function send(path: string, init: RequestInit): Promise<Response> {
    const response = await fetch(new URL(`/api/v1/${path}`, window.location.origin), {
        credentials: 'same-origin',
        ...init,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrfToken(), ...init.headers },
    });

    if (response.status === 401 || response.status === 419) {
        window.location.href = '/login';
        throw new ApiError('Your session has expired.', response.status);
    }

    if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new ApiError(payload.message ?? 'Something went wrong.', response.status, payload.errors ?? {});
    }

    return response;
}

/** Uploads a file as multipart form data and returns the JSON response. */
export async function upload<T>(path: string, file: File, fields: Record<string, string> = {}): Promise<T> {
    const body = new FormData();
    body.append('file', file);
    Object.entries(fields).forEach(([key, value]) => body.append(key, value));

    const response = await send(path, { method: 'POST', body });
    return (await response.json()) as T;
}

/** Downloads a file from the API and saves it with the name the server suggests. */
export async function download(path: string, query: Query = {}, fallbackName = 'download'): Promise<void> {
    const params = new URLSearchParams();
    Object.entries(query).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            params.set(key, String(value));
        }
    });

    const response = await send(`${path}${params.size ? `?${params}` : ''}`, { method: 'GET' });
    const disposition = response.headers.get('Content-Disposition') ?? '';
    const name = /filename="?([^";]+)"?/.exec(disposition)?.[1] ?? fallbackName;
    const url = URL.createObjectURL(await response.blob());

    const link = document.createElement('a');
    link.href = url;
    link.download = name;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}
