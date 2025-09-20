import React from 'react';
import AvatarChip from '../common/AvatarChip';
import FilesBadge from '../common/FilesBadge';

export default function TaskRow({ task = {}, onClick = () => {} }) {
  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: '2-digit' 
    });
  };

  return (
    <div
      className="grid grid-cols-5 gap-4 p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-200"
      onClick={() => onClick(task)}
    >
      <div>
        <div className="flex items-center space-x-2">
          <div className={`w-2 h-2 rounded-full ${task.status === 'open' ? 'bg-blue-500' : 'bg-green-500'}`} />
          <span className="font-medium">{task.title || 'Untitled Task'}</span>
        </div>
        <div className="text-sm text-gray-500 mt-1">
          {task.subtitle || 'No description'}
        </div>
      </div>
      
      <div>
        <FilesBadge count={task.filesCount} />
      </div>
      
      <div className="text-sm text-gray-500">
        {formatDate(task.createdAt)}
      </div>
      
      <div>
        {task.createdBy && <AvatarChip user={task.createdBy} />}
      </div>
      
      <div className="flex space-x-1">
        {task.assignees?.slice(0, 3).map(assignee => (
          <AvatarChip key={assignee.id} user={assignee} size="sm" />
        ))}
        {task.assignees?.length > 3 && (
          <span className="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs">
            +{task.assignees.length - 3}
          </span>
        )}
      </div>
    </div>
  );
}
