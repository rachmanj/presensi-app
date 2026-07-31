import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { LoginForm, ProFormText } from '@ant-design/pro-components';
import { Card, message, theme } from 'antd';
import { login } from '../../services/authService';

export default function LoginPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const { token } = theme.useToken();

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
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: token.colorBgLayout,
      }}
    >
      <Card title="ARKA Presensi" style={{ width: 400 }}>
        <LoginForm
          onFinish={handleSubmit}
          loading={loading}
          containerStyle={{ height: 'auto', overflow: 'hidden', padding: 0 }}
          submitter={{ searchConfig: { submitText: 'Login' } }}
        >
          <ProFormText name="email" label="Email" rules={[{ required: true, type: 'email' }]} />
          <ProFormText.Password name="password" label="Password" rules={[{ required: true }]} />
        </LoginForm>
      </Card>
    </div>
  );
}
