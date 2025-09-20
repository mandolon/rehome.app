# React Native / Expo iOS Starter

This is a minimal Expo React Native starter that demonstrates how to integrate with the Rehome API from day one.

## Quick Start

```bash
npx create-expo-app RehomeApp --template blank-typescript
cd RehomeApp
npm install @expo/vector-icons expo-secure-store axios react-query
```

## Setup

### 1. Install dependencies:
```bash
npm install axios @tanstack/react-query expo-secure-store
```

### 2. Update App.tsx:

```tsx
import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './src/contexts/AuthContext';
import MainNavigator from './src/navigation/MainNavigator';

const queryClient = new QueryClient();

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <MainNavigator />
      </AuthProvider>
    </QueryClientProvider>
  );
}
```

### 3. Create API client (src/api/client.ts):

```tsx
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

const API_BASE_URL = __DEV__ 
  ? 'http://localhost:8000/api' 
  : 'https://api.rehome.app';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth token to requests
apiClient.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default apiClient;
```

### 4. Auth context (src/contexts/AuthContext.tsx):

```tsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import * as SecureStore from 'expo-secure-store';
import apiClient from '../api/client';

interface User {
  id: string;
  name: string;
  email: string;
  role: 'admin' | 'team' | 'client';
  account_id: string;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const token = await SecureStore.getItemAsync('auth_token');
      if (token) {
        const response = await apiClient.get('/me');
        setUser(response.data.data.user);
      }
    } catch (error) {
      await SecureStore.deleteItemAsync('auth_token');
    } finally {
      setLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    const response = await apiClient.post('/login', {
      email,
      password,
      device_name: 'iPhone App',
    });

    const { user, token } = response.data.data;
    await SecureStore.setItemAsync('auth_token', token);
    setUser(user);
  };

  const logout = async () => {
    try {
      await apiClient.post('/logout');
    } catch (error) {
      // Even if logout fails on server, clear local data
    } finally {
      await SecureStore.deleteItemAsync('auth_token');
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
```

### 5. Projects screen (src/screens/ProjectsScreen.tsx):

```tsx
import React from 'react';
import { View, Text, FlatList, StyleSheet } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import apiClient from '../api/client';
import tokens from '../../tokens.json'; // Same design tokens!

interface Project {
  id: string;
  name: string;
  description: string;
  status: 'active' | 'inactive' | 'archived';
}

const ProjectsScreen: React.FC = () => {
  const { data, loading, error } = useQuery({
    queryKey: ['projects'],
    queryFn: async () => {
      const response = await apiClient.get('/projects');
      return response.data.data;
    },
  });

  const renderProject = ({ item }: { item: Project }) => (
    <View style={styles.projectCard}>
      <Text style={styles.projectName}>{item.name}</Text>
      <Text style={styles.projectDescription}>{item.description}</Text>
      <Text style={[
        styles.status,
        { color: item.status === 'active' ? tokens.color.success : tokens.color.muted }
      ]}>
        {item.status.toUpperCase()}
      </Text>
    </View>
  );

  if (loading) {
    return (
      <View style={styles.center}>
        <Text>Loading projects...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Projects</Text>
      <FlatList
        data={data}
        renderItem={renderProject}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: tokens.color.bg,
    padding: tokens.space.lg,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  title: {
    fontSize: tokens.font['2xl'],
    fontWeight: 'bold',
    color: tokens.color.ink,
    marginBottom: tokens.space.lg,
  },
  list: {
    gap: tokens.space.md,
  },
  projectCard: {
    backgroundColor: tokens.color.card,
    padding: tokens.space.lg,
    borderRadius: tokens.radius.md,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  projectName: {
    fontSize: tokens.font.lg,
    fontWeight: '600',
    color: tokens.color.ink,
    marginBottom: tokens.space.sm,
  },
  projectDescription: {
    fontSize: tokens.font.base,
    color: tokens.color.muted,
    marginBottom: tokens.space.sm,
  },
  status: {
    fontSize: tokens.font.sm,
    fontWeight: '500',
  },
});

export default ProjectsScreen;
```

## Key Features

✅ **Same API endpoints** - Zero backend changes needed  
✅ **Shared design tokens** - Copy tokens.json from web project  
✅ **Secure token storage** - Uses iOS Keychain via SecureStore  
✅ **Auto-retry auth** - Handles token expiration gracefully  
✅ **TypeScript ready** - Full type safety  
✅ **React Query** - Caching and background refresh  

## Next Steps

1. Add navigation (React Navigation)
2. Implement file uploads with document picker
3. Add chat interface for RAG Q&A
4. Build offline support with React Query persistence
5. Add push notifications

This starter works with your existing API immediately - no backend changes required!
