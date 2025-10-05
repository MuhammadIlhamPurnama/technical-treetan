# E-Commerce API Documentation

This document describes all the API endpoints available in the system, including authentication, products, and checkout functionality.

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

---

# Products API

## Base URL
```
http://your-domain.com/api/products
```

### 1. Get All Products
**GET** `/products`

Get a paginated list of all products with optional filtering.

**Query Parameters:**
- `status` (optional): Filter by status (`active`, `inactive`)
- `category` (optional): Filter by category
- `search` (optional): Search in product name or description
- `min_price` (optional): Minimum price filter
- `max_price` (optional): Maximum price filter
- `in_stock` (optional): Filter products with stock > 0 (`true`/`false`)
- `sort_by` (optional): Sort field (default: `created_at`)
- `sort_order` (optional): Sort direction (`asc`, `desc`, default: `desc`)
- `per_page` (optional): Items per page (default: 15)

**Success Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Smartphone XYZ",
            "description": "Latest smartphone with advanced features",
            "price": 999.99,
            "formatted_price": "Rp 999.990",
            "stock": 50,
            "category": "Electronics",
            "image": "https://example.com/image.jpg",
            "status": "active",
            "is_available": true,
            "created_at": "2025-10-05 07:30:00",
            "updated_at": "2025-10-05 07:30:00"
        }
    ],
    "links": {
        "first": "http://localhost/api/products?page=1",
        "last": "http://localhost/api/products?page=3",
        "prev": null,
        "next": "http://localhost/api/products?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 45
    }
}
```

### 2. Get Active Products Only
**GET** `/products/active`

Get only active products (same parameters as above).

### 3. Get Product Categories
**GET** `/products/categories`

Get list of all available product categories.

**Success Response (200):**
```json
{
    "success": true,
    "message": "Categories retrieved successfully",
    "data": [
        "Electronics",
        "Clothing",
        "Books",
        "Sports"
    ]
}
```

### 4. Get Single Product
**GET** `/products/{id}`

Get detailed information about a specific product.

**Success Response (200):**
```json
{
    "success": true,
    "message": "Product retrieved successfully",
    "data": {
        "id": 1,
        "name": "Smartphone XYZ",
        "description": "Latest smartphone with advanced features",
        "price": 999.99,
        "formatted_price": "Rp 999.990",
        "stock": 50,
        "category": "Electronics",
        "image": "https://example.com/image.jpg",
        "status": "active",
        "is_available": true,
        "created_at": "2025-10-05 07:30:00",
        "updated_at": "2025-10-05 07:30:00"
    }
}
```

### 5. Create Product (Admin Only)
**POST** `/products`

Create a new product.

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "New Product",
    "description": "Product description",
    "price": 199.99,
    "stock": 100,
    "category": "Electronics",
    "image": "https://example.com/image.jpg",
    "status": "active"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "id": 2,
        "name": "New Product",
        "description": "Product description",
        "price": 199.99,
        "formatted_price": "Rp 199.990",
        "stock": 100,
        "category": "Electronics",
        "image": "https://example.com/image.jpg",
        "status": "active",
        "is_available": true,
        "created_at": "2025-10-05 08:00:00",
        "updated_at": "2025-10-05 08:00:00"
    }
}
```

### 6. Update Product (Admin Only)
**PUT** `/products/{id}`

Update an existing product.

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Request Body (all fields optional):**
```json
{
    "name": "Updated Product Name",
    "price": 299.99,
    "stock": 75
}
```

### 7. Delete Product (Admin Only)
**DELETE** `/products/{id}`

Delete a product.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

---

# Checkout & Orders API

## Base URL
```
http://your-domain.com/api
```

### 1. Calculate Checkout Totals
**POST** `/checkout/calculate`

Calculate totals before placing an order.

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        },
        {
            "product_id": 3,
            "quantity": 1
        }
    ],
    "shipping_amount": 20.00
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Totals calculated successfully",
    "data": {
        "items": [
            {
                "product_id": 1,
                "product_name": "Smartphone XYZ",
                "quantity": 2,
                "unit_price": 999.99,
                "total_price": 1999.98
            }
        ],
        "subtotal": 1999.98,
        "tax_amount": 199.998,
        "tax_rate": 0.1,
        "shipping_amount": 20.00,
        "total_amount": 2219.978
    }
}
```

### 2. Create Order (Checkout)
**POST** `/checkout`

Create a new order.

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ],
    "shipping_address": {
        "name": "John Doe",
        "phone": "081234567890",
        "address": "Jl. Sudirman No. 123",
        "city": "Jakarta",
        "postal_code": "12345",
        "province": "DKI Jakarta",
        "country": "Indonesia"
    },
    "payment_method": "bank_transfer",
    "shipping_amount": 15.00,
    "notes": "Please deliver carefully"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "id": 1,
        "order_number": "ORD-20251005-ABC123",
        "status": "pending",
        "payment_status": "pending",
        "payment_method": "bank_transfer",
        "subtotal": 1999.98,
        "formatted_subtotal": "Rp 1.999.980",
        "tax_amount": 199.998,
        "formatted_tax_amount": "Rp 199.998",
        "shipping_amount": 15.00,
        "formatted_shipping_amount": "Rp 15.000",
        "total_amount": 2214.978,
        "formatted_total_amount": "Rp 2.214.978",
        "shipping_address": {
            "name": "John Doe",
            "phone": "081234567890",
            "address": "Jl. Sudirman No. 123",
            "city": "Jakarta",
            "postal_code": "12345",
            "province": "DKI Jakarta",
            "country": "Indonesia"
        },
        "items": [
            {
                "id": 1,
                "product": {
                    "id": 1,
                    "name": "Smartphone XYZ",
                    "price": 999.99
                },
                "quantity": 2,
                "unit_price": 999.99,
                "formatted_unit_price": "Rp 999.990",
                "total_price": 1999.98,
                "formatted_total_price": "Rp 1.999.980"
            }
        ],
        "can_be_cancelled": true,
        "created_at": "2025-10-05 08:15:00"
    }
}
```

### 3. Get User Orders
**GET** `/orders`

Get user's order history.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Query Parameters:**
- `status` (optional): Filter by order status
- `payment_status` (optional): Filter by payment status
- `sort_by` (optional): Sort field (default: `created_at`)
- `sort_order` (optional): Sort direction (default: `desc`)
- `per_page` (optional): Items per page (default: 10)

**Success Response (200):**
```json
{
    "success": true,
    "message": "Orders retrieved successfully",
    "data": [
        {
            "id": 1,
            "order_number": "ORD-20251005-ABC123",
            "status": "pending",
            "payment_status": "pending",
            "total_amount": 2214.978,
            "formatted_total_amount": "Rp 2.214.978",
            "items_count": 2,
            "created_at": "2025-10-05 08:15:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 5,
        "last_page": 1
    }
}
```

### 4. Get Order Details
**GET** `/orders/{id}`

Get detailed information about a specific order.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Order details retrieved successfully",
    "data": {
        "id": 1,
        "order_number": "ORD-20251005-ABC123",
        "status": "pending",
        "payment_status": "pending",
        "payment_method": "bank_transfer",
        "subtotal": 1999.98,
        "tax_amount": 199.998,
        "shipping_amount": 15.00,
        "total_amount": 2214.978,
        "shipping_address": {
            "name": "John Doe",
            "phone": "081234567890",
            "address": "Jl. Sudirman No. 123"
        },
        "items": [
            {
                "id": 1,
                "product": {
                    "id": 1,
                    "name": "Smartphone XYZ"
                },
                "quantity": 2,
                "unit_price": 999.99,
                "total_price": 1999.98
            }
        ],
        "can_be_cancelled": true,
        "created_at": "2025-10-05 08:15:00"
    }
}
```

### 5. Cancel Order
**POST** `/orders/{id}/cancel`

Cancel an order (only if status is pending or processing).

**Headers:**
```
Authorization: Bearer {your-token}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Order cancelled successfully",
    "data": {
        "id": 1,
        "order_number": "ORD-20251005-ABC123",
        "status": "cancelled",
        "payment_status": "refunded",
        "can_be_cancelled": false
    }
}
```

---

# Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "price": ["The price must be at least 0."]
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Product not found"
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "success": false,
    "message": "Unauthorized access to order"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Internal server error",
    "error": "Detailed error message"
}
```

---

# Status Codes Reference

## Order Status
- `pending`: Order created, awaiting payment
- `processing`: Payment confirmed, preparing order
- `shipped`: Order shipped to customer
- `delivered`: Order delivered successfully
- `cancelled`: Order cancelled

## Payment Status
- `pending`: Awaiting payment
- `paid`: Payment confirmed
- `failed`: Payment failed
- `refunded`: Payment refunded

## Product Status
- `active`: Product available for purchase
- `inactive`: Product not available

---

# cURL Examples

### Get Products
```bash
curl -X GET "http://localhost/api/products?category=Electronics&in_stock=true"
```

### Create Product
```bash
curl -X POST http://localhost/api/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Product",
    "price": 199.99,
    "stock": 50,
    "category": "Electronics"
  }'
```

### Calculate Checkout
```bash
curl -X POST http://localhost/api/checkout/calculate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id": 1, "quantity": 2}
    ]
  }'
```

### Create Order
```bash
curl -X POST http://localhost/api/checkout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"product_id": 1, "quantity": 2}],
    "shipping_address": {
      "name": "John Doe",
      "phone": "081234567890",
      "address": "Jl. Sudirman No. 123",
      "city": "Jakarta",
      "postal_code": "12345",
      "province": "DKI Jakarta",
      "country": "Indonesia"
    },
    "payment_method": "bank_transfer"
  }'
```