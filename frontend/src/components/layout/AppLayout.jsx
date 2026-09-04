import { useState } from 'react';
import { Link, Outlet, useNavigate } from 'react-router-dom';
import {
  UserOutlined,
  LogoutOutlined,
  KeyOutlined,
  BulbOutlined,
  BulbFilled,
} from '@ant-design/icons';
import { ProLayout } from '@ant-design/pro-components';
import { Dropdown, Modal, Form, Input, App } from 'antd';
import { logout, changePassword } from '../../services/authService';
import { useAuth } from '../../hooks/useAuth';
import { useTheme } from '../../hooks/useTheme';

const ALL_MENU_ROUTES = [
  { path: '/dashboard', name: 'Dasbor', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/import', name: 'Impor', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/mapping', name: 'Pemetaan', roles: ['hr_supervisor', 'admin'] },
  { path: '/attendance', name: 'Kehadiran', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/export', name: 'Ekspor', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/comparison', name: 'Perbandingan', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/audit', name: 'Log Audit', roles: ['admin'] },
  {
    path: '/admin',
    name: 'Admin',
    roles: ['hr_supervisor', 'admin'],
    children: [
      { path: '/admin/sites', name: 'Lokasi', roles: ['admin'] },
      { path: '/admin/matrix', name: 'Matriks', roles: ['admin'] },
      { path: '/admin/daytype-codes', name: 'Kode Hari', roles: ['admin'] },
      { path: '/admin/holidays', name: 'Hari Libur', roles: ['hr_supervisor', 'admin'] },
      { path: '/admin/templates', name: 'Template', roles: ['admin'] },
    ],
  },
];

const ROLE_LABELS = {
  admin: 'Admin',
  hr_supervisor: 'Supervisor HR',
  hr_staff: 'Staf HR',
};

function filterMenuByRole(routes, role) {
  return routes
    .filter((item) => item.roles?.includes(role))
    .map((item) => {
      if (item.children) {
        const children = filterMenuByRole(item.children, role);
        if (children.length === 0) return null;
        return { ...item, children };
      }
      return item;
    })
    .filter(Boolean);
}

export default function AppLayout() {
  const navigate = useNavigate();
  const { data: user, refetch } = useAuth();
  const { isDark, toggleTheme } = useTheme();
  const [collapsed, setCollapsed] = useState(false);
  const [passwordModalOpen, setPasswordModalOpen] = useState(false);
  const [changingPassword, setChangingPassword] = useState(false);
  const [passwordForm] = Form.useForm();
  const { message } = App.useApp();

  const menuRoutes = filterMenuByRole(ALL_MENU_ROUTES, user?.role || 'hr_staff');

  const handleLogout = async () => {
    try {
      await logout();
      message.success('Berhasil keluar');
      navigate('/login');
    } catch {
      message.error('Gagal keluar');
    }
  };

  const handleChangePassword = async (values) => {
    setChangingPassword(true);
    try {
      await changePassword(values);
      message.success('Kata sandi berhasil diubah');
      setPasswordModalOpen(false);
      passwordForm.resetFields();
    } catch (err) {
      message.error(err?.response?.data?.message || 'Gagal mengubah kata sandi');
    } finally {
      setChangingPassword(false);
    }
  };

  const userMenuItems = [
    {
      key: 'user-info',
      label: (
        <div style={{ padding: '4px 0' }}>
          <div style={{ fontWeight: 600 }}>{user?.name || 'Pengguna'}</div>
          <div style={{ fontSize: 12, opacity: 0.65 }}>
            {ROLE_LABELS[user?.role] || user?.role}
          </div>
        </div>
      ),
      disabled: true,
    },
    { type: 'divider' },
    {
      key: 'change-password',
      icon: <KeyOutlined />,
      label: 'Ubah Kata Sandi',
      onClick: () => setPasswordModalOpen(true),
    },
    {
      key: 'theme',
      icon: isDark ? <BulbFilled /> : <BulbOutlined />,
      label: isDark ? 'Mode Terang' : 'Mode Gelap',
      onClick: toggleTheme,
    },
    { type: 'divider' },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'Keluar',
      danger: true,
      onClick: handleLogout,
    },
  ];

  return (
    <>
      <ProLayout
        title="ARKA Presensi"
        logo={false}
        collapsed={collapsed}
        onCollapse={setCollapsed}
        route={{ routes: menuRoutes }}
        menuItemRender={(item, dom) => (
          <Link to={item.path || '/'}>{dom}</Link>
        )}
        avatarProps={{
          src: null,
          icon: <UserOutlined />,
          title: user?.name || 'Pengguna',
          render: (_, dom) => (
            <Dropdown menu={{ items: userMenuItems }} trigger={['click']}>
              {dom}
            </Dropdown>
          ),
        }}
      >
        <Outlet />
      </ProLayout>

      <Modal
        title="Ubah Kata Sandi"
        open={passwordModalOpen}
        onCancel={() => {
          setPasswordModalOpen(false);
          passwordForm.resetFields();
        }}
        onOk={() => passwordForm.submit()}
        confirmLoading={changingPassword}
        okText="Simpan"
        cancelText="Batal"
        destroyOnClose
      >
        <Form
          form={passwordForm}
          layout="vertical"
          onFinish={handleChangePassword}
        >
          <Form.Item
            name="current_password"
            label="Kata Sandi Saat Ini"
            rules={[{ required: true, message: 'Masukkan kata sandi saat ini' }]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="password"
            label="Kata Sandi Baru"
            rules={[
              { required: true, message: 'Masukkan kata sandi baru' },
              { min: 8, message: 'Minimal 8 karakter' },
            ]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="password_confirmation"
            label="Konfirmasi Kata Sandi Baru"
            dependencies={['password']}
            rules={[
              { required: true, message: 'Konfirmasi kata sandi baru' },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || getFieldValue('password') === value) {
                    return Promise.resolve();
                  }
                  return Promise.reject(new Error('Kata sandi tidak cocok'));
                },
              }),
            ]}
          >
            <Input.Password />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}
