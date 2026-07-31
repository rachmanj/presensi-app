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
  { path: '/dashboard', name: 'Dashboard', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/import', name: 'Import', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/mapping', name: 'Mapping', roles: ['hr_supervisor', 'admin'] },
  { path: '/attendance', name: 'Attendance', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/export', name: 'Export', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/comparison', name: 'Comparison', roles: ['hr_staff', 'hr_supervisor', 'admin'] },
  { path: '/audit', name: 'Audit Log', roles: ['admin'] },
  {
    path: '/admin',
    name: 'Admin',
    roles: ['hr_supervisor', 'admin'],
    children: [
      { path: '/admin/sites', name: 'Sites', roles: ['admin'] },
      { path: '/admin/matrix', name: 'Matrix', roles: ['admin'] },
      { path: '/admin/daytype-codes', name: 'Daytype Codes', roles: ['admin'] },
      { path: '/admin/holidays', name: 'Holidays', roles: ['hr_supervisor', 'admin'] },
      { path: '/admin/templates', name: 'Templates', roles: ['admin'] },
    ],
  },
];

const ROLE_LABELS = {
  admin: 'Admin',
  hr_supervisor: 'Supervisor',
  hr_staff: 'Staff',
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
      message.success('Logged out');
      navigate('/login');
    } catch {
      message.error('Logout failed');
    }
  };

  const handleChangePassword = async (values) => {
    setChangingPassword(true);
    try {
      await changePassword(values);
      message.success('Password changed successfully');
      setPasswordModalOpen(false);
      passwordForm.resetFields();
    } catch (err) {
      message.error(err?.response?.data?.message || 'Failed to change password');
    } finally {
      setChangingPassword(false);
    }
  };

  const userMenuItems = [
    {
      key: 'user-info',
      label: (
        <div style={{ padding: '4px 0' }}>
          <div style={{ fontWeight: 600 }}>{user?.name || 'User'}</div>
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
      label: 'Change Password',
      onClick: () => setPasswordModalOpen(true),
    },
    {
      key: 'theme',
      icon: isDark ? <BulbFilled /> : <BulbOutlined />,
      label: isDark ? 'Light Mode' : 'Dark Mode',
      onClick: toggleTheme,
    },
    { type: 'divider' },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'Logout',
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
          title: user?.name || 'User',
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
        title="Change Password"
        open={passwordModalOpen}
        onCancel={() => {
          setPasswordModalOpen(false);
          passwordForm.resetFields();
        }}
        onOk={() => passwordForm.submit()}
        confirmLoading={changingPassword}
        destroyOnClose
      >
        <Form
          form={passwordForm}
          layout="vertical"
          onFinish={handleChangePassword}
        >
          <Form.Item
            name="current_password"
            label="Current Password"
            rules={[{ required: true, message: 'Please enter current password' }]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="password"
            label="New Password"
            rules={[
              { required: true, message: 'Please enter new password' },
              { min: 8, message: 'Minimum 8 characters' },
            ]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="password_confirmation"
            label="Confirm New Password"
            dependencies={['password']}
            rules={[
              { required: true, message: 'Please confirm new password' },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || getFieldValue('password') === value) {
                    return Promise.resolve();
                  }
                  return Promise.reject(new Error('Passwords do not match'));
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
