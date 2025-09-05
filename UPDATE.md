# Update Instructions

This document provides instructions for updating the database and using the new API endpoints for the "Try Premium" feature.

## Database Update

Run the following SQL query to add the `has_used_premium_trial` column to the `users` table. This column will track whether a user has already used their one-day premium trial.

```sql
ALTER TABLE `users` ADD `has_used_premium_trial` TINYINT(1) NOT NULL DEFAULT 0;
```

## New API Endpoints

### 1. Check Premium Trial Status

This endpoint checks if a user has already used their one-day premium trial.

**Endpoint:** `/cyd/api/has_used_premium_trial.php`
**Method:** `POST`
**Request Body (JSON):**
```json
{
  "user_id": 123
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "has_used_trial": false
}
```
or
```json
{
  "success": true,
  "has_used_trial": true
}
```

**Error Response (400 Bad Request):**
```json
{
  "error": "Invalid or missing user_id"
}
```

### 2. Activate Premium Trial

This endpoint activates a one-day premium trial for a user if they haven't used it before.

**Endpoint:** `/cyd/api/try_premium.php`
**Method:** `POST`
**Request Body (JSON):**
```json
{
  "user_id": 123
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Premium trial activated for one day."
}
```

**Error Responses:**
- **400 Bad Request:**
  ```json
  {
    "error": "Invalid or missing user_id"
  }
  ```
- **403 Forbidden:**
  ```json
  {
    "error": "Premium trial has already been used."
  }
  ```
