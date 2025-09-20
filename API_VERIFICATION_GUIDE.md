# API Integration Verification Guide

## Quick Setup & Testing

### 1. Environment Setup
```powershell
# Copy .env file if not exists
cp .env.example .env

# Generate app key if needed
php artisan key:generate

# Run migrations and seed demo data
php artisan migrate:fresh --seed --seeder=TaskDemoSeeder
```

### 2. Start Development Servers
```powershell
# Terminal 1 - Laravel API
php artisan serve

# Terminal 2 - React Frontend  
npm run dev
```

### 3. Visit TaskBoard
- Navigate to: `http://localhost:5173/teams/tasks?project=1` 
- Login with: `admin@demo.test` / `password`

## API Integration Features to Verify

### ✅ TaskBoard.jsx Integration
- [ ] Tasks load from API (8 demo tasks should appear)
- [ ] Filter by status works (todo, in_progress, blocked, done)
- [ ] Filter by category works (TASK/REDLINE, PROGRESS/UPDATE)
- [ ] Auto-refresh after creating/editing tasks
- [ ] Fallback to mock data if API fails

### ✅ TaskCreateModal.jsx Integration  
- [ ] Form submits to `/api/v1/projects/1/tasks`
- [ ] Success shows confirmation and closes modal
- [ ] Validation errors display properly
- [ ] Loading spinner during submit
- [ ] TaskBoard refreshes after successful creation

### ✅ TaskDrawer.jsx Integration
- [ ] Task data loads when opening drawer
- [ ] Form updates task via PATCH request
- [ ] Status dropdown shows correct options (todo/in_progress/blocked/done)
- [ ] File upload functionality works
- [ ] Changes reflect immediately in TaskBoard

### ✅ Error Handling & Debugging
- [ ] Console errors show detailed API failure info
- [ ] Network tab shows proper request/response format
- [ ] CSRF tokens handled automatically
- [ ] Loading states prevent double-submits

## Demo Data Details
- **Project ID**: 1
- **Admin User**: admin@demo.test (password: password)  
- **Team User**: team@demo.test (password: password)
- **Tasks**: 8 tasks with mixed statuses and categories
- **Categories**: TASK/REDLINE, PROGRESS/UPDATE
- **Statuses**: todo, in_progress, blocked, done

## API Response Format
All endpoints return consistent envelope:
```json
{
  "ok": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "total": 8,
    "per_page": 50
  }
}
```

## Common Issues & Solutions

### CSRF Token Errors
- Ensure `SANCTUM_STATEFUL_DOMAINS` includes `localhost:5173`
- Clear browser cookies and try again

### API 404 Errors  
- Verify routes with `php artisan route:list --name=api`
- Check project ID exists in URL

### Database Issues
- Re-run: `php artisan migrate:fresh --seed --seeder=TaskDemoSeeder`

### Frontend Not Loading Tasks
- Check browser console for API errors
- Verify backend is running on `http://localhost:8000`
- Check network tab for failed requests

## Success Criteria
✅ All tasks load from API
✅ Create new task works
✅ Edit existing task works  
✅ Status filtering works
✅ Category filtering works
✅ No console errors
✅ All UI components maintain original styling