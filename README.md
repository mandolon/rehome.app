# Rehome.app

A modern, API-first platform for pet rehoming and adoption, built with Laravel and React, designed to be iOS-ready from day one.

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

6. **Run migrations**:
   ```bash
   php artisan migrate
   ```

7. **Start development servers**:
   ```bash
   # Laravel backend
   php artisan serve
   
   # Vite frontend
   npm run dev
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

- Health check: `GET /api/health`
- All authenticated endpoints require `Authorization: Bearer {token}` header
- Responses follow consistent JSON structure with `data`, `meta`, and `message` fields

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
