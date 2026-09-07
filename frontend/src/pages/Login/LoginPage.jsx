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
      message.success('Login berhasil');
      navigate('/dashboard');
    } catch {
      message.error('Email atau kata sandi tidak valid');
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
          submitter={{ searchConfig: { submitText: 'Masuk' } }}
        >
          <ProFormText
            name="email"
            label="Email atau Username"
            fieldProps={{ placeholder: 'Email atau Username' }}
            rules={[{ required: true, message: 'Masukkan email atau username' }]}
          />
          <ProFormText.Password
            name="password"
            label="Kata Sandi"
            rules={[{ required: true, message: 'Masukkan kata sandi' }]}
          />
        </LoginForm>
      </Card>
    </div>
  );
}
