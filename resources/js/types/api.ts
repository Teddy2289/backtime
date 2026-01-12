export interface ApiResponse<T = any> {
    success: boolean;
    message: string;
    data: T;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    links?: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    timestamp: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface ValidationError {
    [key: string]: string[];
}

export interface ApiError {
    message: string;
    errors?: ValidationError;
    status: number;
}
