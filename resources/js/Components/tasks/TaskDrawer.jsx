import React from 'react';

export default function TaskDrawer({ 
  isOpen = false, 
  task = null, 
  onClose = () => {},
  onUpdate = () => {}
}) {
  if (!isOpen || !task) return null;

  const handleSave = () => {
    // TODO: Implement task updating with form data
    // For now, just close the drawer
    onUpdate(task.id, { 
      // Mock update - in real implementation, collect form data
      updatedAt: new Date().toISOString()
    });
  };

  return (
    <>
      {/* Overlay */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 z-40"
        onClick={onClose}
      />
      
      {/* Drawer */}
      <div className="fixed inset-y-0 right-0 w-96 bg-white shadow-xl z-50 flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <span className={`px-2 py-1 text-xs font-semibold uppercase rounded ${
              task.category === 'TASK/REDLINE' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
            }`}>
              {task.category}
            </span>
            {task.status === 'complete' && (
              <span className="ml-2 px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">
                Complete
              </span>
            )}
          </div>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 p-2"
          >
            ✕
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 p-6 overflow-y-auto">
          <h2 className="text-xl font-semibold mb-2">{task.title}</h2>
          <p className="text-gray-600 mb-4">{task.subtitle}</p>
          <p className="text-sm text-gray-500 mb-6">{task.description}</p>
          
          {/* Task Details */}
          <div className="space-y-4 mb-6">
            <div>
              <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                Created By
              </label>
              <div className="flex items-center space-x-2">
                <div className="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-medium">
                  {task.createdBy?.initials || 'UN'}
                </div>
                <span className="text-sm text-gray-900">{task.createdBy?.name || 'Unknown'}</span>
              </div>
            </div>

            {task.assignees && task.assignees.length > 0 && (
              <div>
                <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                  Assignees
                </label>
                <div className="flex flex-wrap gap-2">
                  {task.assignees.map((assignee, index) => (
                    <div key={index} className="flex items-center space-x-2">
                      <div className="w-6 h-6 bg-gray-600 rounded-full flex items-center justify-center text-white text-xs font-medium">
                        {assignee.initials}
                      </div>
                      <span className="text-sm text-gray-900">{assignee.name}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            <div>
              <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                Created Date
              </label>
              <span className="text-sm text-gray-900">
                {task.createdAt && new Date(task.createdAt).toLocaleDateString('en-US', { 
                  month: 'long', 
                  day: 'numeric', 
                  year: 'numeric' 
                })}
              </span>
            </div>

            {task.dueDate && (
              <div>
                <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                  Due Date
                </label>
                <span className="text-sm text-gray-900">
                  {new Date(task.dueDate).toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric', 
                    year: 'numeric' 
                  })}
                </span>
              </div>
            )}
          </div>

          {/* Attachments */}
          {task.attachments && task.attachments.length > 0 && (
            <div className="mb-6">
              <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                Attachments ({task.attachments.length})
              </label>
              <div className="space-y-2">
                {task.attachments.map((attachment) => (
                  <div key={attachment.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                    <div className="flex items-center space-x-3">
                      <span className="text-lg">📄</span>
                      <div>
                        <div className="text-sm font-medium text-gray-900">
                          {attachment.filename}
                        </div>
                        <div className="text-xs text-gray-500">
                          {attachment.size} • {attachment.uploadedBy}
                        </div>
                      </div>
                    </div>
                    <button className="text-gray-400 hover:text-gray-600">
                      ⋯
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Activity */}
          {task.activity && task.activity.length > 0 && (
            <div className="mb-6">
              <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                Activity
              </label>
              <div className="space-y-3">
                {task.activity.map((activity) => (
                  <div key={activity.id} className="flex items-start space-x-3">
                    <div className="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                      <span className="text-white text-xs">
                        {activity.type === 'status_change' && '🔄'}
                        {activity.type === 'assignee_added' && '👤'}
                        {activity.type === 'task_created' && '✨'}
                      </span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm text-gray-900">{activity.description}</p>
                      <p className="text-xs text-gray-500">
                        {activity.user} • {new Date(activity.timestamp).toLocaleDateString()}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="border-t border-gray-200 p-6">
          <div className="flex space-x-3">
            <button 
              onClick={onClose}
              className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
            >
              Cancel
            </button>
            <button 
              onClick={handleSave}
              className="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
            >
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
