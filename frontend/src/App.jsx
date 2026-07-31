import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { ConfigProvider, App as AntApp, theme } from 'antd';
import enUS from 'antd/locale/en_US';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeProvider, useTheme } from './hooks/useTheme';
import AppLayout from './components/layout/AppLayout';
import RequireAuth from './components/layout/RequireAuth';
import RequireRole from './components/layout/RequireRole';
import LoginPage from './pages/Login/LoginPage';
import DashboardPage from './pages/Dashboard/DashboardPage';
import ImportListPage from './pages/Import/ImportListPage';
import ImportUploadPage from './pages/Import/ImportUploadPage';
import EmployeeMappingPage from './pages/Mapping/EmployeeMappingPage';
import PeriodListPage from './pages/Attendance/PeriodListPage';
import SheetDetailPage from './pages/Attendance/SheetDetailPage';
import SheetReviewPage from './pages/Attendance/SheetReviewPage';
import ExportPage from './pages/Export/ExportPage';
import ComparisonPage from './pages/Comparison/ComparisonPage';
import AuditLogPage from './pages/Audit/AuditLogPage';
import SiteConfigPage from './pages/Admin/SiteConfigPage';
import MatrixConfigPage from './pages/Admin/MatrixConfigPage';
import SiteDaytypeCodePage from './pages/Admin/SiteDaytypeCodePage';
import HolidayCalendarPage from './pages/Admin/HolidayCalendarPage';
import ReportTemplatePage from './pages/Admin/ReportTemplatePage';

const { darkAlgorithm, defaultAlgorithm } = theme;

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { refetchOnWindowFocus: false },
  },
});

function ThemedApp() {
  const { isDark } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm: isDark ? darkAlgorithm : defaultAlgorithm,
        token: {
          fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
          borderRadius: 6,
        },
      }}
      locale={enUS}
    >
      <AntApp>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<RequireAuth />}>
              <Route element={<AppLayout />}>
                <Route path="/dashboard" element={<DashboardPage />} />
                <Route path="/import" element={<ImportListPage />} />
                <Route path="/import/upload" element={<ImportUploadPage />} />
                <Route
                  path="/mapping"
                  element={
                    <RequireRole roles={['hr_supervisor', 'admin']}>
                      <EmployeeMappingPage />
                    </RequireRole>
                  }
                />
                <Route path="/attendance" element={<PeriodListPage />} />
                <Route path="/attendance/:periodId" element={<SheetDetailPage />} />
                <Route path="/attendance/sheet/:sheetId/review" element={<SheetReviewPage />} />
                <Route path="/export" element={<ExportPage />} />
                <Route path="/comparison" element={<ComparisonPage />} />
                <Route
                  path="/audit"
                  element={
                    <RequireRole roles={['admin']}>
                      <AuditLogPage />
                    </RequireRole>
                  }
                />
                <Route
                  path="/admin/sites"
                  element={
                    <RequireRole roles={['admin']}>
                      <SiteConfigPage />
                    </RequireRole>
                  }
                />
                <Route
                  path="/admin/matrix"
                  element={
                    <RequireRole roles={['admin']}>
                      <MatrixConfigPage />
                    </RequireRole>
                  }
                />
                <Route
                  path="/admin/daytype-codes"
                  element={
                    <RequireRole roles={['admin']}>
                      <SiteDaytypeCodePage />
                    </RequireRole>
                  }
                />
                <Route
                  path="/admin/holidays"
                  element={
                    <RequireRole roles={['hr_supervisor', 'admin']}>
                      <HolidayCalendarPage />
                    </RequireRole>
                  }
                />
                <Route
                  path="/admin/templates"
                  element={
                    <RequireRole roles={['admin']}>
                      <ReportTemplatePage />
                    </RequireRole>
                  }
                />
                <Route path="/" element={<Navigate to="/dashboard" replace />} />
              </Route>
            </Route>
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </BrowserRouter>
      </AntApp>
    </ConfigProvider>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <ThemedApp />
      </ThemeProvider>
    </QueryClientProvider>
  );
}
