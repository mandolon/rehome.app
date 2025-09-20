import React from 'react';

export default function TaskBoard() {
  const [tasks, setTasks] = React.useState([
    {
      id: '1',
      title: 'Review architectural plans',
      subtitle: 'Ocean View Resort • 123 Beach Blvd, Miami, FL',
      category: 'TASK/REDLINE',
      status: 'open',
      description: 'Review the latest architectural plans for the main lobby renovation. Focus on structural changes and code compliance.',
      createdAt: '2025-06-21T10:30:00Z',
      createdBy: { id: 1, name: 'John Smith', initials: 'JS' },
      assignees: [
        { id: 2, name: 'Sarah Johnson', initials: 'SJ' },
        { id: 3, name: 'Mike Chen', initials: 'MC' }
      ],
      dueDate: '2025-06-30',
      allowClient: false,
      filesCount: 3,
      attachments: [
        { id: 1, filename: 'floor-plan-v2.pdf', size: '2.4 MB', uploadedBy: 'John Smith', uploadedAt: '2025-06-21T10:00:00Z' },
        { id: 2, filename: 'elevation-drawings.dwg', size: '1.8 MB', uploadedBy: 'Sarah Johnson', uploadedAt: '2025-06-21T09:30:00Z' }
      ],
      activity: [
        { id: 1, type: 'status_change', description: 'Status changed: draft → open', timestamp: '2025-06-21T10:30:00Z', user: 'John Smith' },
        { id: 2, type: 'assignee_added', description: 'Sarah Johnson assigned to task', timestamp: '2025-06-21T10:15:00Z', user: 'John Smith' }
      ]
    },
    {
      id: '2',
      title: 'Update client on foundation progress',
      subtitle: 'Downtown Office Complex • 456 Main St, Boston, MA',
      category: 'PROGRESS/UPDATE',
      status: 'open',
      description: 'Prepare weekly progress update for the foundation work including photos and timeline updates.',
      createdAt: '2025-06-20T14:15:00Z',
      createdBy: { id: 3, name: 'Mike Chen', initials: 'MC' },
      assignees: [{ id: 1, name: 'John Smith', initials: 'JS' }],
      dueDate: '2025-06-25',
      allowClient: true,
      filesCount: 1,
      attachments: [],
      activity: []
    },
    {
      id: '3',
      title: 'Electrical system inspection',
      subtitle: 'Ocean View Resort • 123 Beach Blvd, Miami, FL',
      category: 'TASK/REDLINE',
      status: 'complete',
      description: 'Complete electrical inspection for floors 1-3 according to local building codes.',
      createdAt: '2025-06-19T09:00:00Z',
      createdBy: { id: 2, name: 'Sarah Johnson', initials: 'SJ' },
      assignees: [{ id: 4, name: 'Alex Rivera', initials: 'AR' }],
      dueDate: '2025-06-22',
      allowClient: false,
      filesCount: 0,
      attachments: [],
      activity: []
    }
  ]);

  const [searchQuery, setSearchQuery] = React.useState('');
  const [selectedTask, setSelectedTask] = React.useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);
  const [showClosed, setShowClosed] = React.useState(false);
  const [collapsedGroups, setCollapsedGroups] = React.useState({});
  const [editData, setEditData] = React.useState({});

  const mockUsers = [
    { id: 1, name: 'John Smith', initials: 'JS' },
    { id: 2, name: 'Sarah Johnson', initials: 'SJ' },
    { id: 3, name: 'Mike Chen', initials: 'MC' },
    { id: 4, name: 'Alex Rivera', initials: 'AR' },
    { id: 5, name: 'Emma Wilson', initials: 'EW' }
  ];

  // Helper functions
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: '2-digit' 
    }).replace(',', '');
  };

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

  // URL sync for task drawer
  React.useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get('task');
    
    if (taskId && !isDrawerOpen) {
      const task = tasks.find(t => t.id === taskId);
      if (task) {
        setSelectedTask(task);
        setIsDrawerOpen(true);
      }
    } else if (!taskId && isDrawerOpen) {
      setIsDrawerOpen(false);
      setSelectedTask(null);
    }
  }, [tasks, isDrawerOpen]);

  const updateURL = (taskId) => {
    const url = new URL(window.location);
    if (taskId) {
      url.searchParams.set('task', taskId);
    } else {
      url.searchParams.delete('task');
    }
    window.history.replaceState({}, '', url);
  };

  React.useEffect(() => {
    if (selectedTask) {
      setEditData({
        title: selectedTask.title || '',
        description: selectedTask.description || '',
        status: selectedTask.status || 'open',
        assigneeIds: selectedTask.assignees?.map(a => a.id) || [],
        dueDate: selectedTask.dueDate || '',
        allowClient: selectedTask.allowClient || false
      });
    }
  }, [selectedTask]);

  const filteredTasks = tasks.filter(task => {
    const matchesSearch = !searchQuery || 
      task.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      task.subtitle.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesStatus = showClosed || task.status !== 'complete';
    
    return matchesSearch && matchesStatus;
  });

  const groupedTasks = {
    'TASK/REDLINE': filteredTasks.filter(task => task.category === 'TASK/REDLINE'),
    'PROGRESS/UPDATE': filteredTasks.filter(task => task.category === 'PROGRESS/UPDATE')
  };

  const handleTaskClick = (task) => {
    setSelectedTask(task);
    setIsDrawerOpen(true);
    updateURL(task.id);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedTask(null);
    updateURL(null);
  };

  const handleTaskUpdate = (taskId, updateData) => {
    setTasks(prev => prev.map(task => 
      task.id === taskId 
        ? { 
            ...task, 
            ...updateData,
            assignees: updateData.assigneeIds 
              ? mockUsers.filter(user => updateData.assigneeIds.includes(user.id))
              : task.assignees
          }
        : task
    ));
    setIsDrawerOpen(false);
    updateURL(null);
  };

  const toggleGroup = (groupName) => {
    setCollapsedGroups(prev => ({
      ...prev,
      [groupName]: !prev[groupName]
    }));
  };

  return (
    <>
      <style>{`
        .task-board-root {
          --bg-primary: #ffffff;
          --bg-secondary: #f8f9fa;
          --bg-tertiary: #f1f3f4;
          --text-primary: #202124;
          --text-secondary: #5f6368;
          --text-muted: #9aa0a6;
          --border-color: #dadce0;
          --accent-primary: #1a73e8;
          --success: #137333;
          font-family: 'Google Sans', Roboto, sans-serif;
        }
        
        [data-theme="dark"] .task-board-root {
          --bg-primary: #1f1f1f;
          --bg-secondary: #2d2d30;
          --bg-tertiary: #3c3c3c;
          --text-primary: #cccccc;
          --text-secondary: #969696;
          --text-muted: #6c6c6c;
          --border-color: #3c3c3c;
          --accent-primary: #4285f4;
          --success: #34a853;
        }

        .avatar-chip {
          width: 24px;
          height: 24px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 10px;
          font-weight: 500;
          color: white;
        }

        .status-dot {
          width: 6px;
          height: 6px;
          border-radius: 50%;
          display: inline-block;
        }

        .status-open { background-color: var(--accent-primary); }
        .status-complete { background-color: var(--success); }

        .task-row {
          cursor: pointer;
          transition: background-color 0.15s ease;
        }

        .task-row:hover {
          background-color: var(--bg-tertiary);
        }

        .drawer-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: rgba(0, 0, 0, 0.4);
          z-index: 1000;
        }

        .drawer-content {
          position: fixed;
          top: 0;
          right: 0;
          height: 100vh;
          width: 400px;
          background-color: var(--bg-primary);
          box-shadow: -2px 0 8px rgba(0, 0, 0, 0.15);
          overflow-y: auto;
          transform: translateX(100%);
          transition: transform 0.3s ease;
          z-index: 1001;
        }

        .drawer-content.open {
          transform: translateX(0);
        }

        @media (max-width: 768px) {
          .drawer-content {
            width: 100%;
          }
        }

        .group-pill {
          display: inline-block;
          padding: 2px 8px;
          border-radius: 10px;
          font-size: 10px;
          font-weight: 600;
          text-transform: uppercase;
          color: white;
        }

        .pill-task { background-color: #1a73e8; }
        .pill-progress { background-color: #34a853; }

        .filter-button {
          display: flex;
          align-items: center;
          gap: 4px;
          padding: 4px 8px;
          font-size: 12px;
          color: var(--text-secondary);
          background: transparent;
          border: 1px solid transparent;
          border-radius: 4px;
          cursor: pointer;
          transition: all 0.15s ease;
        }

        .filter-button:hover {
          background-color: var(--bg-tertiary);
          border-color: var(--border-color);
        }

        .files-badge {
          background-color: var(--bg-tertiary);
          color: var(--text-secondary);
          padding: 2px 6px;
          border-radius: 3px;
          font-size: 11px;
          font-weight: 500;
        }

        .input-field {
          width: 100%;
          padding: 8px 12px;
          border: 1px solid var(--border-color);
          border-radius: 4px;
          background-color: var(--bg-primary);
          color: var(--text-primary);
          font-size: 14px;
        }

        .input-field:focus {
          outline: none;
          border-color: var(--accent-primary);
          box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .textarea-field {
          width: 100%;
          padding: 12px;
          border: 1px solid var(--border-color);
          border-radius: 4px;
          background-color: var(--bg-primary);
          color: var(--text-primary);
          font-size: 14px;
          min-height: 80px;
          resize: vertical;
          font-family: inherit;
          line-height: 1.4;
        }

        .textarea-field:focus {
          outline: none;
          border-color: var(--accent-primary);
          box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .btn {
          padding: 8px 16px;
          border-radius: 4px;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.15s ease;
          border: none;
          display: inline-flex;
          align-items: center;
          gap: 8px;
        }

        .btn-primary {
          background-color: var(--accent-primary);
          color: white;
        }

        .btn-primary:hover {
          background-color: #1557b0;
        }

        .btn-secondary {
          background-color: var(--bg-secondary);
          color: var(--text-primary);
          border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
          background-color: var(--bg-tertiary);
        }

        .status-toggle {
          display: flex;
          background-color: var(--bg-secondary);
          border-radius: 4px;
          border: 1px solid var(--border-color);
          overflow: hidden;
        }

        .status-toggle button {
          flex: 1;
          padding: 8px 16px;
          border: none;
          background: transparent;
          color: var(--text-secondary);
          font-size: 14px;
          cursor: pointer;
          transition: all 0.15s ease;
        }

        .status-toggle button.active {
          background-color: var(--accent-primary);
          color: white;
        }
      `}</style>

      <div className="task-board-root h-full bg-background flex flex-col overflow-hidden" style={{ backgroundColor: 'var(--bg-primary)', color: 'var(--text-primary)' }}>
        {/* Header */}
        <div style={{ borderBottom: '1px solid var(--border-color)', padding: '4px 16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div></div>
          </div>
        </div>

        {/* Filters */}
        <div style={{ padding: '8px 16px', borderBottom: '1px solid var(--border-color)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
            <span style={{ fontSize: '12px', fontWeight: 'bold', color: 'var(--text-secondary)', paddingRight: '8px' }}>Group by:</span>
            
            <button className="filter-button">
              Status
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="6,9 12,15 18,9"></polyline>
              </svg>
            </button>
            
            <button className="filter-button">
              Projects
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="6,9 12,15 18,9"></polyline>
              </svg>
            </button>
            
            <button className="filter-button">
              Date Created
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="6,9 12,15 18,9"></polyline>
              </svg>
            </button>
            
            <button className="filter-button">
              Assignee
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="6,9 12,15 18,9"></polyline>
              </svg>
            </button>
            
            <button className="filter-button">
              Created by
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="6,9 12,15 18,9"></polyline>
              </svg>
            </button>

            <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <input 
                type="text"
                placeholder="Search tasks..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                style={{
                  padding: '6px 12px',
                  border: '1px solid var(--border-color)',
                  borderRadius: '4px',
                  fontSize: '14px',
                  width: '200px',
                  backgroundColor: 'var(--bg-primary)',
                  color: 'var(--text-primary)'
                }}
              />
              
              <button className="btn btn-secondary" disabled>+ Add Note</button>
              <button className="btn btn-secondary" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                  <path d="m5,16-3,-3 2,-2 3,3"></path>
                  <path d="m13,13 3,-3"></path>
                </svg>
                Screen Clip
              </button>
              <button className="btn btn-secondary" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <path d="m9,9 5,5"></path>
                  <path d="m15,9-5,5"></path>
                </svg>
                ToDo
              </button>
              
              <label style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '14px' }}>
                <input 
                  type="checkbox"
                  checked={showClosed}
                  onChange={(e) => setShowClosed(e.target.checked)}
                />
                Completed
              </label>
              
              <button className="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Task
              </button>
            </div>
          </div>
        </div>

        {/* Content */}
        <div style={{ flex: 1, padding: '16px', backgroundColor: 'var(--bg-primary)', overflowY: 'auto' }}>
          <div style={{ maxWidth: '100%', margin: '0 auto' }}>
            {Object.entries(groupedTasks).map(([groupName, groupTasks]) => (
              <div key={groupName} style={{ marginBottom: '24px' }}>
                {/* Group Header */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }}>
                  <button
                    onClick={() => toggleGroup(groupName)}
                    style={{ 
                      background: 'none', 
                      border: 'none', 
                      padding: '2px', 
                      cursor: 'pointer',
                      color: 'var(--text-secondary)'
                    }}
                  >
                    {collapsedGroups[groupName] ? 
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                      </svg> :
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <polyline points="6,9 12,15 18,9"></polyline>
                      </svg>
                    }
                  </button>
                  <span className={`group-pill ${groupName === 'TASK/REDLINE' ? 'pill-task' : 'pill-progress'}`}>
                    {groupName}
                  </span>
                  <span style={{
                    backgroundColor: 'white',
                    color: '#333',
                    padding: '2px 8px',
                    borderRadius: '10px',
                    fontSize: '12px',
                    fontWeight: '600',
                    minWidth: '24px',
                    textAlign: 'center'
                  }}>
                    {groupTasks.length}
                  </span>
                </div>

                {!collapsedGroups[groupName] && (
                  <div style={{ 
                    backgroundColor: 'var(--bg-secondary)', 
                    border: '1px solid var(--border-color)', 
                    borderRadius: '8px',
                    overflow: 'hidden'
                  }}>
                    {/* Table Header */}
                    <div style={{
                      display: 'grid',
                      gridTemplateColumns: '1fr 80px 120px 120px 140px',
                      gap: '16px',
                      padding: '12px 16px',
                      backgroundColor: 'var(--bg-tertiary)',
                      fontSize: '11px',
                      fontWeight: '600',
                      textTransform: 'uppercase',
                      color: 'var(--text-secondary)',
                      borderBottom: '1px solid var(--border-color)'
                    }}>
                      <div>Name</div>
                      <div>Files</div>
                      <div>Date Created</div>
                      <div>Created by</div>
                      <div>Assigned to</div>
                    </div>

                    {/* Task Rows */}
                    {groupTasks.map(task => (
                      <div
                        key={task.id}
                        className="task-row"
                        onClick={() => handleTaskClick(task)}
                        style={{
                          display: 'grid',
                          gridTemplateColumns: '1fr 80px 120px 120px 140px',
                          gap: '16px',
                          alignItems: 'center',
                          padding: '12px 16px',
                          backgroundColor: 'var(--bg-primary)',
                          borderBottom: '1px solid var(--border-color)'
                        }}
                      >
                        <div>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' }}>
                            <span className={`status-dot status-${task.status}`} />
                            <span style={{ fontWeight: '500', fontSize: '14px' }}>{task.title}</span>
                          </div>
                          <div style={{ fontSize: '12px', color: 'var(--text-secondary)' }}>
                            {task.subtitle}
                          </div>
                        </div>
                        
                        <div>
                          {task.filesCount > 0 && (
                            <span className="files-badge">
                              📎 {task.filesCount}
                            </span>
                          )}
                        </div>
                        
                        <div style={{ fontSize: '12px', color: 'var(--text-secondary)' }}>
                          {formatDate(task.createdAt)}
                        </div>
                        
                        <div>
                          <div 
                            className="avatar-chip"
                            style={{ backgroundColor: getAvatarColor(task.createdBy.name) }}
                            title={task.createdBy.name}
                          >
                            {task.createdBy.initials}
                          </div>
                        </div>
                        
                        <div style={{ display: 'flex', gap: '4px', alignItems: 'center' }}>
                          {task.assignees.slice(0, 3).map(assignee => (
                            <div 
                              key={assignee.id}
                              className="avatar-chip"
                              style={{ backgroundColor: getAvatarColor(assignee.name) }}
                              title={assignee.name}
                            >
                              {assignee.initials}
                            </div>
                          ))}
                          {task.assignees.length > 3 && (
                            <span style={{
                              backgroundColor: 'var(--bg-tertiary)',
                              color: 'var(--text-secondary)',
                              width: '24px',
                              height: '24px',
                              borderRadius: '50%',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              fontSize: '10px',
                              fontWeight: '600'
                            }}>
                              +{task.assignees.length - 3}
                            </span>
                          )}
                        </div>
                      </div>
                    ))}

                    {groupTasks.length === 0 && (
                      <div style={{ 
                        padding: '32px', 
                        textAlign: 'center', 
                        color: 'var(--text-secondary)',
                        backgroundColor: 'var(--bg-primary)'
                      }}>
                        No tasks found
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Task Detail Drawer */}
        {isDrawerOpen && (
          <>
            <div className="drawer-overlay" onClick={handleDrawerClose} />
            <div className={`drawer-content ${isDrawerOpen ? 'open' : ''}`}>
              {/* Header */}
              <div style={{ padding: '24px', borderBottom: '1px solid var(--border-color)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '16px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <span className={`group-pill ${selectedTask?.category === 'TASK/REDLINE' ? 'pill-task' : 'pill-progress'}`}>
                      {selectedTask?.category}
                    </span>
                  </div>
                  <button 
                    onClick={handleDrawerClose}
                    style={{
                      background: 'none',
                      border: 'none',
                      fontSize: '18px',
                      cursor: 'pointer',
                      color: 'var(--text-secondary)',
                      padding: '4px'
                    }}
                  >
                    ✕
                  </button>
                </div>

                {/* Status Toggle */}
                <div style={{ marginBottom: '16px' }}>
                  <div className="status-toggle">
                    <button 
                      className={editData.status === 'open' ? 'active' : ''}
                      onClick={() => setEditData({ ...editData, status: 'open' })}
                    >
                      Open
                    </button>
                    <button 
                      className={editData.status === 'complete' ? 'active' : ''}
                      onClick={() => setEditData({ ...editData, status: 'complete' })}
                    >
                      Complete
                    </button>
                  </div>
                </div>

                {/* Editable Title */}
                <input 
                  className="input-field"
                  type="text"
                  value={editData.title || ''}
                  onChange={(e) => setEditData({ ...editData, title: e.target.value })}
                  style={{ 
                    fontSize: '20px', 
                    fontWeight: '600', 
                    marginBottom: '8px',
                    border: 'none',
                    padding: '8px 0',
                    background: 'transparent'
                  }}
                  placeholder="Task title"
                />
                
                <p style={{ color: 'var(--text-secondary)', margin: 0, fontSize: '14px' }}>
                  {selectedTask?.subtitle}
                </p>
              </div>

              <div style={{ padding: '24px' }}>
                {/* Description */}
                <div style={{ marginBottom: '32px' }}>
                  <label style={{ display: 'block', marginBottom: '12px', fontSize: '14px', fontWeight: '600' }}>
                    Description
                  </label>
                  <textarea 
                    className="textarea-field"
                    value={editData.description || ''}
                    onChange={(e) => setEditData({ ...editData, description: e.target.value })}
                    placeholder="Add a description..."
                    style={{ minHeight: '120px' }}
                  />
                </div>

                {/* Metadata Grid */}
                <div style={{ 
                  display: 'grid', 
                  gridTemplateColumns: '1fr 1fr', 
                  gap: '24px 16px',
                  marginBottom: '32px'
                }}>
                  {/* Created By */}
                  <div>
                    <label style={{ 
                      display: 'block', 
                      fontSize: '12px', 
                      fontWeight: '600', 
                      color: 'var(--text-secondary)', 
                      textTransform: 'uppercase', 
                      marginBottom: '8px' 
                    }}>
                      Created By
                    </label>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                      <div 
                        className="avatar-chip"
                        style={{ backgroundColor: getAvatarColor(selectedTask?.createdBy?.name || '') }}
                      >
                        {selectedTask?.createdBy?.initials}
                      </div>
                      <span style={{ fontSize: '14px' }}>{selectedTask?.createdBy?.name}</span>
                    </div>
                  </div>

                  {/* Date Created */}
                  <div>
                    <label style={{ 
                      display: 'block', 
                      fontSize: '12px', 
                      fontWeight: '600', 
                      color: 'var(--text-secondary)', 
                      textTransform: 'uppercase', 
                      marginBottom: '8px' 
                    }}>
                      Date Created
                    </label>
                    <span style={{ fontSize: '14px' }}>
                      {selectedTask?.createdAt && new Date(selectedTask.createdAt).toLocaleDateString('en-US', { 
                        month: 'long', 
                        day: 'numeric', 
                        year: 'numeric' 
                      })}
                    </span>
                  </div>

                  {/* Assignees */}
                  <div style={{ gridColumn: '1 / -1' }}>
                    <label style={{ 
                      display: 'block', 
                      fontSize: '12px', 
                      fontWeight: '600', 
                      color: 'var(--text-secondary)', 
                      textTransform: 'uppercase', 
                      marginBottom: '8px' 
                    }}>
                      Assignees
                    </label>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', marginBottom: '8px' }}>
                      {selectedTask?.assignees?.map(assignee => (
                        <div key={assignee.id} style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <div 
                            className="avatar-chip"
                            style={{ backgroundColor: getAvatarColor(assignee.name) }}
                          >
                            {assignee.initials}
                          </div>
                          <span style={{ fontSize: '14px' }}>{assignee.name}</span>
                        </div>
                      ))}
                    </div>
                    <select 
                      className="input-field"
                      onChange={(e) => {
                        if (e.target.value) {
                          const userId = parseInt(e.target.value);
                          if (!editData.assigneeIds?.includes(userId)) {
                            setEditData({ 
                              ...editData, 
                              assigneeIds: [...(editData.assigneeIds || []), userId] 
                            });
                          }
                          e.target.value = '';
                        }
                      }}
                    >
                      <option value="">Add assignee...</option>
                      {mockUsers.filter(user => !editData.assigneeIds?.includes(user.id)).map(user => (
                        <option key={user.id} value={user.id}>{user.name}</option>
                      ))}
                    </select>
                  </div>

                  {/* Due Date */}
                  <div>
                    <label style={{ 
                      display: 'block', 
                      fontSize: '12px', 
                      fontWeight: '600', 
                      color: 'var(--text-secondary)', 
                      textTransform: 'uppercase', 
                      marginBottom: '8px' 
                    }}>
                      Due Date
                    </label>
                    <input 
                      className="input-field"
                      type="date"
                      value={editData.dueDate || ''}
                      onChange={(e) => setEditData({ ...editData, dueDate: e.target.value })}
                    />
                  </div>
                </div>

                {/* Allow Client */}
                <div style={{ marginBottom: '32px' }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '14px', cursor: 'pointer' }}>
                    <input 
                      type="checkbox"
                      checked={editData.allowClient || false}
                      onChange={(e) => setEditData({ ...editData, allowClient: e.target.checked })}
                    />
                    Allow Client Access
                  </label>
                </div>

                {/* Attachments */}
                <div style={{ marginBottom: '32px' }}>
                  <label style={{ 
                    display: 'block', 
                    marginBottom: '12px', 
                    fontSize: '14px', 
                    fontWeight: '600' 
                  }}>
                    Attachments ({selectedTask?.attachments?.length || 0})
                  </label>
                  
                  <div style={{
                    border: '2px dashed var(--border-color)',
                    borderRadius: '8px',
                    padding: '32px',
                    textAlign: 'center',
                    color: 'var(--text-secondary)',
                    backgroundColor: 'var(--bg-secondary)',
                    marginBottom: '16px',
                    cursor: 'pointer'
                  }}>
                    📎 Drop files here or click to upload
                  </div>

                  {selectedTask?.attachments?.map(attachment => (
                    <div key={attachment.id} style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      padding: '12px',
                      backgroundColor: 'var(--bg-secondary)',
                      border: '1px solid var(--border-color)',
                      borderRadius: '6px',
                      marginBottom: '8px'
                    }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flex: 1 }}>
                        <span style={{ fontSize: '20px' }}>📄</span>
                        <div>
                          <div style={{ fontWeight: '500', fontSize: '14px' }}>
                            {attachment.filename}
                          </div>
                          <div style={{ fontSize: '12px', color: 'var(--text-secondary)' }}>
                            {attachment.size} • {attachment.uploadedBy} • {formatDate(attachment.uploadedAt)}
                          </div>
                        </div>
                      </div>
                      <button style={{ 
                        background: 'none', 
                        border: 'none', 
                        color: 'var(--text-secondary)', 
                        cursor: 'pointer' 
                      }}>
                        ⋯
                      </button>
                    </div>
                  ))}
                </div>

                {/* Activity Feed */}
                <div style={{ marginBottom: '32px' }}>
                  <label style={{ 
                    display: 'block', 
                    marginBottom: '12px', 
                    fontSize: '14px', 
                    fontWeight: '600' 
                  }}>
                    Activity
                  </label>
                  
                  <div>
                    {selectedTask?.activity?.map(activity => (
                      <div key={activity.id} style={{
                        display: 'flex',
                        alignItems: 'flex-start',
                        gap: '12px',
                        padding: '12px 0',
                        borderBottom: '1px solid var(--border-color)'
                      }}>
                        <div style={{
                          width: '24px',
                          height: '24px',
                          borderRadius: '50%',
                          backgroundColor: 'var(--accent-primary)',
                          color: 'white',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontSize: '12px',
                          flexShrink: 0,
                          marginTop: '2px'
                        }}>
                          {activity.type === 'status_change' && '🔄'}
                          {activity.type === 'assignee_added' && '👤'}
                          {activity.type === 'task_created' && '✨'}
                        </div>
                        <div style={{ flex: 1 }}>
                          <div style={{ fontSize: '14px', marginBottom: '4px' }}>
                            {activity.description}
                          </div>
                          <div style={{ fontSize: '12px', color: 'var(--text-secondary)' }}>
                            {activity.user} • {formatDate(activity.timestamp)}
                          </div>
                        </div>
                      </div>
                    ))}
                    {(!selectedTask?.activity || selectedTask.activity.length === 0) && (
                      <div style={{ color: 'var(--text-secondary)', fontSize: '14px' }}>
                        No recent activity
                      </div>
                    )}
                  </div>
                </div>

                {/* Actions */}
                <div style={{ 
                  display: 'flex', 
                  gap: '12px', 
                  paddingTop: '16px', 
                  borderTop: '1px solid var(--border-color)',
                  position: 'sticky',
                  bottom: '0',
                  backgroundColor: 'var(--bg-primary)'
                }}>
                  <button 
                    className="btn btn-secondary"
                    onClick={handleDrawerClose}
                    style={{ flex: 1 }}
                  >
                    Cancel
                  </button>
                  <button 
                    className="btn btn-primary"
                    onClick={() => handleTaskUpdate(selectedTask.id, editData)}
                    style={{ flex: 1 }}
                  >
                    Save Changes
                  </button>
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </>
  );
}
