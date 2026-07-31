import { Navigate } from 'react-router-dom';
import { Result } from 'antd';
import { useAuth } from '../../hooks/useAuth';

export default function RequireRole({ roles, children }) {
  const { data: user, isLoading } = useAuth();

  if (isLoading) return null;

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (!roles.includes(user.role)) {
    return (
      <Result
        status="403"
        title="Access Denied"
        subTitle="You do not have permission to view this page."
      />
    );
  }

  return children;
}
