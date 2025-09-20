import React, { useState, useEffect } from 'react';

const TaskDrawer = ({ isOpen, task, onClose, onUpdate, mockUsers }) => {
  const [editData, setEditData] = useState({
    title: '',
    description: '',
    status: 'open',
    assigneeIds: [],
    dueDate: ''
  });

  useEffect(() => {
    if (task) {
      setEditData({
        title: task.title || '',
        description: task.description || '',
        status: task.status || 'open',
        assigneeIds: task.assignees?.map(a => a.id) || [],
        dueDate: task.dueDate || ''
      });
    }
  }, [task]);

  const handleSave = () => {
    if (task) {
      onUpdate(task.id, editData);
    }
  };

  if (!isOpen || !task) return null;

  return (
    <>
      <div className="drawer-overlay" onClick={onClose} />
      <div className={`drawer-content ${isOpen ? 'open' : ''}`}>
        <div style={{ padding: '1.5rem', borderBottom: '1px solid var(--border-color)' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
            <h2 style={{ margin: 0, fontSize: '1.25rem', fontWeight: '600' }}>Task Details</h2>
            <button 
              className="btn btn-secondary"
              onClick={onClose}
              style={{ padding: '0.5rem' }}
            >
              ✕
            </button>
          </div>
          
          <input 
            className="input"
            type="text"
            value={editData.title}
            onChange={(e) => setEditData({ ...editData, title: e.target.value })}
            style={{ fontSize: '1.125rem', fontWeight: '600', marginBottom: '0.5rem' }}
            placeholder="Task title"
          />
          
          <p style={{ color: 'var(--text-secondary)', margin: 0, fontSize: '0.875rem' }}>
            {task.subtitle}
          </p>
        </div>

        <div style={{ padding: '1.5rem' }}>
          <div style={{ marginBottom: '1.5rem' }}>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.875rem', fontWeight: '500' }}>
              Description
            </label>
            <textarea 
              className="input"
              value={editData.description}
              onChange={(e) => setEditData({ ...editData, description: e.target.value })}
              placeholder="Add a description..."
              rows={4}
              style={{ resize: 'vertical' }}
            />
          </div>

          <div style={{ marginBottom: '1.5rem' }}>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.875rem', fontWeight: '500' }}>
              Status
            </label>
            <select 
              className="select"
              value={editData.status}
              onChange={(e) => setEditData({ ...editData, status: e.target.value })}
            >
              <option value="open">Open</option>
              <option value="complete">Complete</option>
            </select>
          </div>

          <div style={{ marginBottom: '1.5rem' }}>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.875rem', fontWeight: '500' }}>
              Assignees
            </label>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem', marginBottom: '0.5rem' }}>
              {task.assignees?.map(assignee => (
                <div key={assignee.id} style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                  <AvatarChip user={assignee} size="sm" />
                  <span style={{ fontSize: '0.875rem' }}>{assignee.name}</span>
                </div>
              ))}
            </div>
            <select 
              className="select"
              onChange={(e) => {
                if (e.target.value) {
                  const userId = parseInt(e.target.value);
                  if (!editData.assigneeIds.includes(userId)) {
                    setEditData({ 
                      ...editData, 
                      assigneeIds: [...editData.assigneeIds, userId] 
                    });
                  }
                  e.target.value = '';
                }
              }}
            >
              <option value="">Add assignee...</option>
              {mockUsers?.filter(user => !editData.assigneeIds.includes(user.id)).map(user => (
                <option key={user.id} value={user.id}>{user.name}</option>
              ))}
            </select>
          </div>

          <div style={{ marginBottom: '1.5rem' }}>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.875rem', fontWeight: '500' }}>
              Due Date
            </label>
            <input 
              className="input"
              type="date"
              value={editData.dueDate}
              onChange={(e) => setEditData({ ...editData, dueDate: e.target.value })}
            />
          </div>

          <div style={{ marginBottom: '1.5rem' }}>
            <label style={{ display: 'block', marginBottom: '0.75rem', fontSize: '0.875rem', fontWeight: '500' }}>
              Attachments
            </label>
            <div 
              style={{
                border: '2px dashed var(--border-color)',
                borderRadius: '8px',
                padding: '2rem',
                textAlign: 'center',
                color: 'var(--text-secondary)'
              }}
            >
              📎 Drop files here or click to upload
            </div>
          </div>

          <div style={{ marginBottom: '1.5rem' }}>
            <h4 style={{ margin: '0 0 0.75rem 0', fontSize: '0.875rem', fontWeight: '500' }}>
              Recent Activity
            </h4>
            <div style={{ color: 'var(--text-secondary)', fontSize: '0.875rem' }}>
              No recent activity
            </div>
          </div>

          <div style={{ display: 'flex', gap: '0.75rem', paddingTop: '1rem', borderTop: '1px solid var(--border-color)' }}>
            <button 
              className="btn btn-secondary"
              onClick={onClose}
            >
              Cancel
            </button>
            <button 
              className="btn btn-primary"
              onClick={handleSave}
            >
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </>
  );
};

// Avatar component helper
const AvatarChip = ({ user, size = 'sm' }) => {
  const getAvatarColor = (name) => {
    const colors = [
      '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
      '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
      '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
      '#ec4899', '#f43f5e'
    ];
    const index = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    return colors[index % colors.length];
  };

  const sizeClasses = {
    sm: { width: '24px', height: '24px', fontSize: '0.625rem' },
    md: { width: '32px', height: '32px', fontSize: '0.75rem' },
    lg: { width: '40px', height: '40px', fontSize: '0.875rem' }
  };

  return (
    <div 
      className="avatar"
      style={{
        backgroundColor: getAvatarColor(user.name),
        ...sizeClasses[size]
      }}
      title={user.name}
    >
      {user.initials}
    </div>
  );
};

export default TaskDrawer;
