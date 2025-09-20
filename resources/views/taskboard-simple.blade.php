<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏗️ Construction Task Board</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .stats { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .tasks-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .task-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #007bff; }
        .task-card.complete { border-left-color: #28a745; }
        .task-card.open { border-left-color: #007bff; }
        .task-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 8px; }
        .task-description { color: #666; margin-bottom: 12px; line-height: 1.4; }
        .task-meta { display: flex; justify-content: space-between; font-size: 12px; color: #999; }
        .task-category { background: #e9ecef; padding: 2px 8px; border-radius: 4px; }
        .task-status { padding: 2px 8px; border-radius: 4px; color: white; }
        .task-status.open { background: #007bff; }
        .task-status.complete { background: #28a745; }
        .due-date { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ Construction Task Board</h1>
        
        <div class="stats">
            <h3>Project Tasks Overview</h3>
            <p>Total Tasks: <strong>{{ count($tasks) }}</strong> | 
               Open: <strong>{{ $tasks->where('status', 'open')->count() }}</strong> | 
               Complete: <strong>{{ $tasks->where('status', 'complete')->count() }}</strong></p>
        </div>

        @if($tasks->count() > 0)
            <div class="tasks-grid">
                @foreach($tasks as $task)
                    <div class="task-card {{ $task->status }}">
                        <div class="task-title">{{ $task->title }}</div>
                        <div class="task-description">{{ $task->description }}</div>
                        <div class="task-meta">
                            <div>
                                <span class="task-category">{{ $task->category }}</span>
                                <span class="task-status {{ $task->status }}">{{ ucfirst($task->status) }}</span>
                            </div>
                            @if($task->due_date)
                                <div class="due-date">Due: {{ date('M j, Y', strtotime($task->due_date)) }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 40px; background: white; border-radius: 8px;">
                <p>No tasks found.</p>
            </div>
        @endif
    </div>
</body>
</html>