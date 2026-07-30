import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { LoginForm, ProFormText } from '@ant-design/pro-components';
import { Card, message } from 'antd';
import { login } from '../../services/authService';

export default function LoginPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await login(values.email, values.password);
      message.success('Login successful');
      navigate('/dashboard');
    } catch {
      message.error('Invalid email or password');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#f0f2f5' }}>
      <Card title="ARKA Presensi" style={{ width: 400 }}>
        <LoginForm onFinish={handleSubmit} loading={loading} submitter={{ searchConfig: { submitText: 'Login' } }}>
          <ProFormText name="email" label="Email" rules={[{ required: true, type: 'email' }]} />
          <ProFormText.Password name="password" label="Password" rules={[{ required: true }]} />
        </LoginForm>
      </Card>
    </div>
  );
}
