export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role: 'admin' | 'counselor' | 'client';
}

export interface NotificationItem {
    id: string;
    read_at: string | null;
    created_at: string;
    event: string | null;
    title: string;
    message: string;
    url: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        counselor_photo_url: string | null;
    };
    notifications: {
        unread_count: number;
        items: NotificationItem[];
    };
    flash: {
        success: string | null;
        error: string | null;
    };
};
