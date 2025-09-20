import React from 'react';

export default function SimpleTaskBoard({ tasks = [], error = null }) {
  console.log('TaskBoard component loaded', { tasks, error });
  
  return (
    <div style={{ padding: '20px', fontSize: '18px', fontFamily: 'Arial' }}>
      <h1 style={{ color: 'blue' }}>🏗️ Construction Task Board</h1>
      <p>Component loaded successfully!</p>
      <p>Tasks count: {tasks.length}</p>
      {error && <p style={{ color: 'red' }}>Error: {error}</p>}
      
      <div style={{ marginTop: '20px', backgroundColor: '#f0f0f0', padding: '10px' }}>
        <h3>First Few Tasks:</h3>
        {tasks.slice(0, 3).map((task, index) => (
          <div key={task.id || index} style={{ margin: '10px 0', border: '1px solid #ccc', padding: '8px' }}>
            <strong>{task.title || 'No title'}</strong>
            <br />
            <small>{task.description || 'No description'}</small>
          </div>
        ))}
      </div>
    </div>
  );
}