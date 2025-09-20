# Rehome.app

A modern, API-first preconstruction platform focusing on design, permit, and building workflows. Built with Laravel and React, designed to be iOS-ready from day one.

## 🏗️ Architecture Overview

This project follows an **API-first** approach, making it ready for both web and mobile development:

- **Backend**: Laravel 10+ with API-first design
- **Frontend**: React with Inertia.js for seamless SPA experience  
- **Mobile Ready**: Same REST APIs will power future iOS/React Native app
- **Auth**: Laravel Sanctum (session for web, tokens for mobile)
- **Multi-tenant**: Account-based isolation with role-based access control

## 🚀 Features

### ✅ Built & Ready
- Multi-tenant architecture (accounts → users → projects)
- Role-based access control (admin/team/client)
- RESTful API with proper authorization policies
- File management with signed URLs
- RAG (Retrieval Augmented Generation) system for document Q&A
- Design token system shared between web and future mobile
- CORS configured for mobile development

### 🔧 API Endpoints

#### Authentication
- `POST /api/login` - Login (returns token for mobile)
- `GET /api/me` - Get current user
- `POST /api/logout` - Logout

#### Projects
- `GET /api/projects` - List projects (role-scoped)
- `POST /api/projects` - Create project (admin/team only)
- `GET /api/projects/{id}` - Get project details
- `PATCH /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project (admin only)

#### Documents & RAG
- `POST /api/projects/{id}/docs` - Upload documents (queued processing)
- `POST /api/projects/{id}/ask` - Ask questions about project docs

#### Files
- `GET /api/files/{id}` - Get file metadata + signed download URL
- `GET /api/files/{id}/download` - Download file (signed URL)

### 📱 Mobile-Ready Features
- **Pagination**: All list endpoints support `?page=1&per_page=20`
- **Lightweight payloads**: Optimized JSON responses
- **Stable enums**: Consistent status/role values
- **Signed URLs**: Secure file access without exposing storage paths
- **Token auth**: Ready for iOS Keychain storage
- **Design tokens**: JSON file shared between web and React Native

## 🗂️ Project Structure

```
app/
├── Http/Controllers/Api/     # Mobile-safe API controllers
├── Models/                   # Eloquent models
├── Policies/                 # Authorization policies  
├── Services/                 # Business logic (RAG, Embeddings)
database/migrations/          # Multi-tenant schema
resources/
├── js/
│   ├── tokens.json          # Design tokens (web + mobile)
│   └── app.tsx              # React/Inertia entry point
routes/api.php               # API routes
```

## 🛠️ Setup Instructions

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 18+
- MySQL/PostgreSQL

### Installation

1. **Install PHP dependencies**:
   ```bash
   composer install
   ```

2. **Install Node dependencies**:
   ```bash
   npm install
   ```

3. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database** in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=rehome_app
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Add OpenAI API key** for RAG functionality:
   ```env
   OPENAI_API_KEY=your_openai_api_key
   ```

6. **Run migrations and seed demo data**:
   ```bash
   php artisan migrate --seed
   ```

7. **Start development servers**:
   ```bash
   # Laravel backend
   php artisan serve
   
   # Vite frontend (in separate terminal)
   npm run dev
   ```

## 🧪 Demo Data & Testing

After seeding, you'll have realistic preconstruction demo data ready for testing:

### 📋 Demo Accounts
- **Apex Construction Group** - Enterprise account with admin and team users
- **Urban Development Partners** - Pro account with client user

### 🔐 Demo Credentials
```
Admin:  admin@apex-construction.com / password
Team:   team@apex-construction.com / password  
Client: client@urban-dev.com / password
```

### 🏗️ Demo Projects
1. **Downtown Mixed-Use Development** - Permitting phase, MU-3 zoning
2. **Riverside Residential Complex** - Design Development phase, R-4 zoning
3. **Tech Campus Expansion** - Schematic Design phase, C-2 zoning

Each project includes realistic documents with embedded content for RAG testing.

### 🔄 API Testing with cURL

#### 1. Authentication
```bash
# Login to get token
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@apex-construction.com",
    "password": "password"
  }'

# Response: { "ok": true, "data": { "user": {...}, "token": "1|abc123..." } }
```

#### 2. Projects Management
```bash
# Set your token
export TOKEN="1|your-token-from-login"

# List projects (with search)
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/v1/projects?q=downtown&per_page=10"

# Get project details
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/v1/projects/1"

# Create new project (admin/team only)
curl -X POST http://localhost:8000/api/v1/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Office Building",
    "description": "Corporate headquarters with parking",
    "phase": "Feasibility Study",
    "zoning": "C-1 Commercial"
  }'

# Update project
curl -X PATCH http://localhost:8000/api/v1/projects/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phase": "Construction Documents",
    "description": "Updated project scope"
  }'
```

#### 3. Document Upload & RAG
```bash
# Upload documents for processing
curl -X POST http://localhost:8000/api/v1/projects/1/docs \
  -H "Authorization: Bearer $TOKEN" \
  -F "files[]=@/path/to/zoning-report.pdf" \
  -F "files[]=@/path/to/building-plans.dwg"

# Ask questions about project documents (RAG)
curl -X POST http://localhost:8000/api/v1/projects/1/ask \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "What are the setback requirements for this project?"
  }'

# Response: {
#   "ok": true,
#   "answer": "Based on the zoning documents, the setback requirements are...",
#   "citations": [
#     {"doc_id": 123, "chunk_no": 2},
#     {"doc_id": 124, "chunk_no": 0}
#   ]
# }
```

#### 4. File Management
```bash
# Get file metadata and signed download URL
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/v1/files/1"

# Response includes download_url with 15-minute signature:
# { "ok": true, "data": { "download_url": "http://localhost:8000/api/v1/files/1/download?signature=..." } }

# Download file using signed URL (no auth needed)
curl -o downloaded-file.pdf \
  "http://localhost:8000/api/v1/files/1/download?signature=abc123&expires=1234567890"
```

#### 5. Health Checks
```bash
# System health check
curl "http://localhost:8000/api/v1/health"

# Readiness check (includes database/migrations)
curl "http://localhost:8000/api/v1/ready"
```

#### 6. User Management
```bash
# Get current user profile
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/v1/me"

# Logout (revokes token)
curl -X POST http://localhost:8000/api/v1/logout \
  -H "Authorization: Bearer $TOKEN"
```

### 🧪 Rate Limiting Examples
```bash
# Login attempts (5 per minute per IP)
for i in {1..6}; do
  curl -X POST http://localhost:8000/api/v1/login \
    -H "Content-Type: application/json" \
    -d '{"email": "wrong@email.com", "password": "wrong"}'
done
# 6th attempt returns: { "ok": false, "message": "Too Many Requests" } with 429 status

# API requests (120 per minute per authenticated user)
# Watch for X-RateLimit-Remaining header in responses
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/v1/projects" \
  -I | grep -i ratelimit
```

## 🎨 Design System

The project uses a token-based design system (`resources/js/tokens.json`) that can be shared between web and React Native:

```json
{
  "color": {
    "ink": "#171717",
    "accent": "#db0f83",
    "bg": "#fafafb"
  },
  "space": { "sm": 8, "md": 12, "lg": 16 },
  "radius": { "sm": 6, "md": 10, "lg": 14 }
}
```

## 🔐 Authentication & Authorization

### Roles
- **Admin**: Full account access, can delete projects
- **Team**: Can create/manage projects, upload documents  
- **Client**: Can view own projects, upload to own projects

### Multi-tenancy
- All data is scoped to `account_id`
- Users can only access data within their account
- Policies enforce role-based permissions

## 📱 iOS Development Path

When ready to build the iOS app:

1. **Use Expo/React Native** with the existing `/api/*` endpoints
2. **Reuse design tokens** from `tokens.json`  
3. **Token authentication** with Keychain storage
4. **Same data shapes** as web app
5. **File downloads** via signed URLs work natively

## 🧪 Testing

Run the test suite:
```bash
./vendor/bin/pest
```

## 📝 API Documentation

### Response Format
All API responses follow a consistent envelope structure:

**Success Response (2xx)**:
```json
{
  "ok": true,
  "data": { /* response payload */ },
  "meta": { /* pagination/metadata */ },
  "message": "Optional success message"
}
```

**Error Response (4xx/5xx)**:
```json
{
  "ok": false,
  "error": "ValidationException",
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Common HTTP Status Codes
- **200 OK** - Successful request
- **201 Created** - Resource created successfully  
- **401 Unauthorized** - Missing or invalid authentication token
- **403 Forbidden** - User lacks permission for this action
- **422 Unprocessable Entity** - Validation errors or insufficient context (RAG)
- **429 Too Many Requests** - Rate limit exceeded
- **500 Internal Server Error** - Server error (logged for debugging)

### Rate Limiting
- **Login endpoints**: 5 attempts per minute per IP address
- **Authenticated API**: 120 requests per minute per user
- Rate limit headers included: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

### Authentication
- **Web**: Session-based authentication via login form
- **API/Mobile**: Token-based with `Authorization: Bearer {token}` header
- **Tokens**: Generated via `/api/v1/login`, revoked via `/api/v1/logout`

### File Uploads
- **Supported formats**: PDF, DOC, DOCX, TXT, MD
- **Size limit**: 10MB per file, 5 files per request
- **Processing**: Files are queued for text extraction, chunking, and embedding

### RAG (Retrieval-Augmented Generation)
- **Model**: GPT-4o with preconstruction-focused system prompt
- **Embeddings**: OpenAI text-embedding-3-small (1536 dimensions)
- **Context**: Top 12 most relevant document chunks (0.6+ similarity)
- **Citations**: Returns `doc_id` and `chunk_no` for source verification

For complete API specification, see `openapi.yaml` in the project root.

## 🚧 Next Steps

1. Install PHP/Composer and run `composer install`
2. Set up database and run migrations
3. Add sample data via seeders
4. Build React components for Dashboard, Projects, ProjectDetail
5. Test API endpoints with frontend
6. Deploy to staging environment
7. Start iOS development when web app is stable

## 🤝 Contributing

This project is designed to be contributor-friendly with clear separation of concerns and comprehensive testing.

## 📄 License

MIT License - see LICENSE file for details.

---

**Ready for Cursor AI development!** 🎉

The project structure is complete and follows Laravel best practices while maintaining iOS readiness. All core APIs are implemented with proper authorization, file handling, and RAG functionality.
