import { useState } from 'react';
import { Link, Outlet, useNavigate } from 'react-router-dom';
import { LogoutOutlined } from '@ant-design/icons';
import { ProLayout } from '@ant-design/pro-components';
import { Dropdown, message } from 'antd';
import { logout } from '../../services/authService';
import { useAuth } from '../../hooks/useAuth';

const menuRoutes = [
  { path: '/dashboard', name: 'Dashboard' },
  { path: '/import', name: 'Import' },
  { path: '/mapping', name: 'Mapping' },
  { path: '/attendance', name: 'Attendance' },
  { path: '/export', name: 'Export' },
  {
    path: '/admin',
    name: 'Admin',
    children: [
      { path: '/admin/sites', name: 'Sites' },
      { path: '/admin/matrix', name: 'Matrix' },
      { path: '/admin/daytype-codes', name: 'Daytype Codes' },
      { path: '/admin/holidays', name: 'Holidays' },
      { path: '/admin/templates', name: 'Templates' },
    ],
  },
];

export default function AppLayout() {
  const navigate = useNavigate();
  const { data: user } = useAuth();
  const [collapsed, setCollapsed] = useState(false);

  const handleLogout = async () => {
    try {
      await logout();
      message.success('Logged out');
      navigate('/login');
    } catch {
      message.error('Logout failed');
    }
  };

  return (
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
        title: user?.name || 'User',
        render: (_, dom) => (
          <Dropdown
            menu={{
              items: [
                {
                  key: 'logout',
                  icon: <LogoutOutlined />,
                  label: 'Logout',
                  onClick: handleLogout,
                },
              ],
            }}
          >
            {dom}
          </Dropdown>
        ),
      }}
    >
      <Outlet />
    </ProLayout>
  );
}
