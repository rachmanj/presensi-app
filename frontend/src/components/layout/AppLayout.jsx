import { useState } from 'react';
import { Link, Outlet, useNavigate } from 'react-router-dom';
import { LogoutOutlined } from '@ant-design/icons';
import { ProLayout } from '@ant-design/pro-components';
import { Dropdown, message } from 'antd';
import { logout } from '../../services/authService';
import { useAuth } from '../../hooks/useAuth';

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
  const { data: user } = useAuth();
  const [collapsed, setCollapsed] = useState(false);

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
