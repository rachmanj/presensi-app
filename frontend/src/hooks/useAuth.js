import { useQuery } from '@tanstack/react-query';
import { getCurrentUser } from '../services/authService';

export function useAuth() {
  return useQuery({
    queryKey: ['auth', 'me'],
    queryFn: getCurrentUser,
    retry: false,
    staleTime: 5 * 60 * 1000,
  });
}
