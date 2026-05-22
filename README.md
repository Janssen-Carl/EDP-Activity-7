# e-Library PHP Backend

This backend implements the 3 main targets using PHP + MySQL:

1. User Authentication
2. Password Recovery
3. User Management

It is aligned with your SQL entity model in `user_elibrary`, `member_account`, `librarian_account`, and `guest_account`.

## 1) Prerequisites

- PHP 8.1+
- MySQL 8+
- Existing database from `elibrary_system-SQL.sql`

## 2) Setup

1. Copy environment template:

```bash
cp backend/.env.example backend/.env
```

2. Update DB credentials in `backend/.env`.

3. Apply your base schema and seed:
- Run `elibrary_system-SQL.sql` in MySQL.

4. Apply backend migration:
- Run `backend/database/migrations/001_auth_and_session_tables.sql`.

5. Seed auth credentials for existing users:

```bash
php backend/tools/seed_credentials.php
```

## 3) Run API

From project root:

```bash
php -S localhost:8000 -t backend/public
```

Health check:

```bash
curl http://localhost:8000/api/health
```

## 4) Main API Endpoints

### Authentication

- `POST /api/auth/login`

Request:

```json
{
  "email": "member@library.edu",
  "password": "Member@123",
  "userType": "Member"
}
```

### Password Recovery

- `POST /api/auth/forgot-password`

Request:

```json
{
  "email": "member@library.edu"
}
```

Note: In development mode, response includes `devResetToken` so you can test reset flow.

- `POST /api/auth/reset-password`

Request:

```json
{
  "token": "<devResetToken>",
  "newPassword": "NewStrongPass@123"
}
```

- `POST /api/auth/change-password` (requires Bearer token)

Request:

```json
{
  "currentPassword": "Member@123",
  "newPassword": "NewStrongPass@123"
}
```

### User Management

- `GET /api/users?search=&type=&status=`
- `GET /api/users/{id}`
- `POST /api/users`
- `PUT /api/users/{id}`
- `PATCH /api/users/{id}/status`

Example create user:

```json
{
  "fullName": "John Doe",
  "email": "john.doe@library.edu",
  "phoneNumber": "09170000000",
  "userType": "Member",
  "accountStatus": "Active",
  "password": "John@12345"
}
```

## 5) Frontend Integration Notes

Your current frontend still uses local mock JS logic. To connect it to this backend, update scripts under `scripts/` to call `/api/...` endpoints.

Recommended first integrations:
- `scripts/login.js` -> `POST /api/auth/login`
- `scripts/forgot-password.js` -> `POST /api/auth/forgot-password`
- `scripts/change-password.js` -> `POST /api/auth/change-password`
- `scripts/user-management.js` -> `GET /api/users`, `PATCH /api/users/{id}/status`
- `scripts/create-account.js` -> `POST /api/users`

## 6) Security Notes

- Passwords are stored as `password_hash()` values.
- Session tokens are random 64-char tokens in DB with expiration.
- Reset tokens are stored hashed (`sha256`) with expiry + single-use.
- Set `APP_ENV=production` to hide dev reset token output.
