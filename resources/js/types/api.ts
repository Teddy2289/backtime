export interface ApiResponse<T = any> {
    data: T;
    message: string;
    status: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
}

export interface ValidationError {
    [key: string]: string[];
}

export interface ApiError {
    message: string;
    errors?: ValidationError;
    status: number;
}
