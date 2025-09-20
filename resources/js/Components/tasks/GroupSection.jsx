import React from 'react';
import TaskRow from './TaskRow';

export default function GroupSection({ 
  groupName = 'Group', 
  groupTasks = [], 
  isCollapsed = false, 
  onToggle = () => {}, 
  onTaskClick = () => {} 
}) {
  return (
    <div className="mb-6">
      {/* Group Header */}
      <div className="flex items-center space-x-3 mb-2">
        <button
          onClick={onToggle}
          className="p-1 hover:bg-gray-100 rounded"
        >
          {isCollapsed ? (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          ) : (
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          )}
        </button>
        <span className={`px-2 py-1 text-xs font-semibold uppercase rounded ${
          groupName === 'TASK/REDLINE' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
        }`}>
          {groupName}
        </span>
        <span className="bg-white text-gray-800 px-2 py-1 text-xs font-semibold rounded-full min-w-6 text-center">
          {groupTasks.length}
        </span>
      </div>

      {!isCollapsed && (
        <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
          {/* Table Header */}
          <div className="grid grid-cols-5 gap-4 p-4 bg-gray-50 border-b border-gray-200">
            <div className="text-xs font-semibold text-gray-500 uppercase">Name</div>
            <div className="text-xs font-semibold text-gray-500 uppercase">Files</div>
            <div className="text-xs font-semibold text-gray-500 uppercase">Date Created</div>
            <div className="text-xs font-semibold text-gray-500 uppercase">Created by</div>
            <div className="text-xs font-semibold text-gray-500 uppercase">Assigned to</div>
          </div>

          {/* Task Rows */}
          {groupTasks.map(task => (
            <TaskRow 
              key={task.id} 
              task={task} 
              onClick={onTaskClick}
            />
          ))}

          {groupTasks.length === 0 && (
            <div className="p-8 text-center text-gray-500">
              No tasks found
            </div>
          )}
        </div>
      )}
    </div>
  );
}
