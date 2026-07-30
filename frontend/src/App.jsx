import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AppLayout from './components/layout/AppLayout';
import RequireAuth from './components/layout/RequireAuth';
import LoginPage from './pages/Login/LoginPage';
import DashboardPage from './pages/Dashboard/DashboardPage';
import SiteConfigPage from './pages/Admin/SiteConfigPage';
import MatrixConfigPage from './pages/Admin/MatrixConfigPage';
import SiteDaytypeCodePage from './pages/Admin/SiteDaytypeCodePage';
import HolidayCalendarPage from './pages/Admin/HolidayCalendarPage';
import ReportTemplatePage from './pages/Admin/ReportTemplatePage';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { refetchOnWindowFocus: false },
  },
});

function PlaceholderPage({ title }) {
  return (
    <div style={{ padding: 24 }}>
      <h2>{title}</h2>
      <p>Coming in Fase 1.</p>
    </div>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ConfigProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<RequireAuth />}>
              <Route element={<AppLayout />}>
                <Route path="/dashboard" element={<DashboardPage />} />
                <Route path="/import" element={<PlaceholderPage title="Import Fingerprint" />} />
                <Route path="/mapping" element={<PlaceholderPage title="Employee Mapping" />} />
                <Route path="/attendance" element={<PlaceholderPage title="Attendance" />} />
                <Route path="/export" element={<PlaceholderPage title="Export" />} />
                <Route path="/admin/sites" element={<SiteConfigPage />} />
                <Route path="/admin/matrix" element={<MatrixConfigPage />} />
                <Route path="/admin/daytype-codes" element={<SiteDaytypeCodePage />} />
                <Route path="/admin/holidays" element={<HolidayCalendarPage />} />
                <Route path="/admin/templates" element={<ReportTemplatePage />} />
                <Route path="/" element={<Navigate to="/dashboard" replace />} />
              </Route>
            </Route>
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </BrowserRouter>
      </ConfigProvider>
    </QueryClientProvider>
  );
}
