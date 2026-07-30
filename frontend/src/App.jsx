import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AppLayout from './components/layout/AppLayout';
import RequireAuth from './components/layout/RequireAuth';
import LoginPage from './pages/Login/LoginPage';
import DashboardPage from './pages/Dashboard/DashboardPage';
import ImportListPage from './pages/Import/ImportListPage';
import ImportUploadPage from './pages/Import/ImportUploadPage';
import EmployeeMappingPage from './pages/Mapping/EmployeeMappingPage';
import PeriodListPage from './pages/Attendance/PeriodListPage';
import SheetDetailPage from './pages/Attendance/SheetDetailPage';
import SheetReviewPage from './pages/Attendance/SheetReviewPage';
import ExportPage from './pages/Export/ExportPage';
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
                <Route path="/import" element={<ImportListPage />} />
                <Route path="/import/upload" element={<ImportUploadPage />} />
                <Route path="/mapping" element={<EmployeeMappingPage />} />
                <Route path="/attendance" element={<PeriodListPage />} />
                <Route path="/attendance/:periodId" element={<SheetDetailPage />} />
                <Route path="/attendance/sheet/:sheetId/review" element={<SheetReviewPage />} />
                <Route path="/export" element={<ExportPage />} />
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
