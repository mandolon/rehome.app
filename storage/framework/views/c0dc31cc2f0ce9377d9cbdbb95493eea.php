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
            <p>Total Tasks: <strong><?php echo e(count($tasks)); ?></strong> | 
               Open: <strong><?php echo e($tasks->where('status', 'open')->count()); ?></strong> | 
               Complete: <strong><?php echo e($tasks->where('status', 'complete')->count()); ?></strong></p>
        </div>

        <?php if($tasks->count() > 0): ?>
            <div class="tasks-grid">
                <?php $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="task-card <?php echo e($task->status); ?>">
                        <div class="task-title"><?php echo e($task->title); ?></div>
                        <div class="task-description"><?php echo e($task->description); ?></div>
                        <div class="task-meta">
                            <div>
                                <span class="task-category"><?php echo e($task->category); ?></span>
                                <span class="task-status <?php echo e($task->status); ?>"><?php echo e(ucfirst($task->status)); ?></span>
                            </div>
                            <?php if($task->due_date): ?>
                                <div class="due-date">Due: <?php echo e(date('M j, Y', strtotime($task->due_date))); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 8px;">
                <p>No tasks found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html><?php /**PATH C:\Users\maria\Documents\New folder (3)\rehome.app\resources\views/taskboard-simple.blade.php ENDPATH**/ ?>