# Authentication API Documentation

This document describes the authentication endpoints available in the API.

## Base URL
```
http://your-domain.com/api
```

## Authentication
This API uses Bearer token authentication with Laravel Sanctum. Include the token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Endpoints

### 1. Register User
**POST** `/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": null,
            "created_at": "2025-10-04T14:00:00.000000Z",
            "updated_at": "2025-10-04T14:00:00.000000Z"
        },
        "token": "1|abc123def456...",
        "token_type": "Bearer"
    }
}
```

**Validation Error Response (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."]
    }
}
```

### 2. Login User
**POST** `/auth/login`

Login with existing credentials.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": null,
            "created_at": "2025-10-04T14:00:00.000000Z",
            "updated_at": "2025-10-04T14:00:00.000000Z"
        },
        "token": "2|xyz789abc123...",
        "token_type": "Bearer"
    }
}
```

**Invalid Credentials Response (422):**
```json
{
    "success": false,
    "message": "Invalid credentials",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

### 3. Get Current User
**GET** `/auth/me`

Get the authenticated user's information.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "User data retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": null,
            "created_at": "2025-10-04T14:00:00.000000Z",
            "updated_at": "2025-10-04T14:00:00.000000Z"
        }
    }
}
```

### 4. Refresh Token
**POST** `/auth/refresh`

Refresh the current authentication token.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "3|new789token123...",
        "token_type": "Bearer"
    }
}
```

### 5. Logout
**POST** `/auth/logout`

Logout and invalidate the current token.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

### 6. Get User Profile
**GET** `/user`

Get the authenticated user's profile (alternative endpoint).

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2025-10-04T14:00:00.000000Z",
    "updated_at": "2025-10-04T14:00:00.000000Z"
}
```

## Error Responses

### Unauthenticated (401)
```json
{
    "message": "Unauthenticated."
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Login failed",
    "error": "Internal server error message"
}
```

## Validation Rules

### Registration
- `name`: required, string, max 255 characters
- `email`: required, valid email, unique in users table
- `password`: required, min 8 characters, must be confirmed

### Login
- `email`: required, valid email format
- `password`: required, string

## Security Features

1. **Password Hashing**: All passwords are hashed using Laravel's Hash facade
2. **Token-based Authentication**: Uses Laravel Sanctum for API token management
3. **Token Revocation**: Previous tokens are revoked on login/refresh
4. **Input Validation**: All inputs are validated with custom error messages
5. **Rate Limiting**: Can be configured in Laravel's rate limiting middleware
6. **CORS Support**: Configured through Sanctum middleware

## Testing the API

### Using cURL

**Register:**
```bash
curl -X POST http://your-domain.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Login:**
```bash
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Get User Info:**
```bash
curl -X GET http://your-domain.com/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Logout:**
```bash
curl -X POST http://your-domain.com/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```