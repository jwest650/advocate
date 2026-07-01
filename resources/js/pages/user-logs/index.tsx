import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import { History, Eye } from 'lucide-react';

export default function UserLogs() {
  const { t } = useTranslation();
  const { auth, logs, user, filters: pageFilters = {} } = usePage().props as any;

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isViewModalOpen, setIsViewModalOpen] = useState(false);
  const [selectedLog, setSelectedLog] = useState<any>(null);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    const params: any = { page: 1 };
    if (searchTerm) params.search = searchTerm;
    if (pageFilters.per_page) params.per_page = pageFilters.per_page;

    const routeName = user ? 'users.logs' : 'user-logs.index';
    const routeParams = user ? [user.id, params] : [params];
    router.get(route(routeName, ...routeParams), { preserveState: true, preserveScroll: true });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setShowFilters(false);

    const params: any = { page: 1, per_page: pageFilters.per_page };
    const routeName = user ? 'users.logs' : 'user-logs.index';
    const routeParams = user ? [user.id, params] : [params];
    router.get(route(routeName, ...routeParams), { preserveState: true, preserveScroll: true });
  };

  const hasActiveFilters = () => searchTerm !== '';
  const activeFilterCount = () => searchTerm ? 1 : 0;

  const getRoleBadgeColor = (role: string) => {
    return 'bg-green-500';
  };

  const handleViewDetails = (log: any) => {
    setSelectedLog(log);
    setIsViewModalOpen(true);
  };

  const pageActions = [];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Users'), href: route('users.index') },
    { title: t('Login History') }
  ];

  return (
    <PageTemplate
      title={t("User Login History")}
      url="/user-logs"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Search and filters section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            const params: any = { page: 1, per_page: parseInt(value) };
            if (searchTerm) params.search = searchTerm;

            const routeName = user ? 'users.logs' : 'user-logs.index';
            const routeParams = user ? [user.id, params] : [params];
            router.get(route(routeName, ...routeParams), { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                <th className="px-4 py-3 text-left font-medium text-gray-500">#</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">
                  <div className="flex items-center">
                    {t("User")}
                  </div>
                </th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">
                  <div className="flex items-center">
                    {t("IP Address")}
                  </div>
                </th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t("Location & Device")}</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">
                  <div className="flex items-center">
                    {t("Role")}
                  </div>
                </th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">
                  <div className="flex items-center">
                    {t("Time")}
                  </div>
                </th>
                <th className="px-4 py-3 text-right font-medium text-gray-500">{t("Actions")}</th>
              </tr>
            </thead>
            <tbody>
              {logs?.data?.map((log: any, index: number) => (
                <tr key={log.id} className="border-b hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                  <td className="px-4 py-3 text-gray-500">
                    {((logs.current_page - 1) * logs.per_page) + index + 1}
                  </td>
                  <td className="px-4 py-3">
                    <div>
                      <div className="font-medium text-gray-900 dark:text-white">{log.user?.name || 'Unknown User'}</div>
                      <div className="text-xs text-gray-500 dark:text-gray-400">{log.user?.email || 'No email'}</div>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm text-gray-900 dark:text-white">{log.ip || '::1'}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="text-sm text-gray-900 dark:text-white space-y-1">
                      <div>{log.details?.country || 'Unknown'}</div>
                      <div className="text-gray-500 dark:text-gray-400">
                        {log.details?.browser || 'Chrome'} on {log.details?.os || 'Linux'}
                      </div>
                      <div className="text-gray-500 dark:text-gray-400">
                        {log.details?.device || 'Desktop'}
                      </div>
                      <div className="text-gray-500 dark:text-gray-400">
                        Lang: {log.details?.lang || 'en'}
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-green-600 font-medium">
                      {log.type || 'User'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm text-gray-900 dark:text-white">
                      {new Date(log.created_at).toLocaleDateString('en-CA')} {new Date(log.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleViewDetails(log)}
                      className="text-blue-500 hover:text-blue-700"
                    >
                      <Eye className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              ))}

              {(!logs?.data || logs.data.length === 0) && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    {t("No login history found")}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination section */}
        <Pagination
          from={logs?.from || 0}
          to={logs?.to || 0}
          total={logs?.total || 0}
          links={logs?.links}
          entityName={t("entries")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      {/* View Details Modal */}
      <Dialog open={isViewModalOpen} onOpenChange={setIsViewModalOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle >
              {t("View User Logs")}
            </DialogTitle>
          </DialogHeader>
          {selectedLog && (
            <div className="grid grid-cols-2 gap-x-8 gap-y-3 p-4 text-sm">
              <div>
                <span className="font-medium text-gray-600">{t("Status")}</span>
                <div className="text-gray-900">{selectedLog.details?.status || 'success'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Country")}</span>
                <div className="text-gray-900">{selectedLog.details?.country || 'Unknown'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Country Code")}</span>
                <div className="text-gray-900">{selectedLog.details?.countryCode || 'N/A'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Region")}</span>
                <div className="text-gray-900">{selectedLog.details?.region || 'N/A'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Region Name")}</span>
                <div className="text-gray-900">{selectedLog.details?.regionName || 'N/A'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("City")}</span>
                <div className="text-gray-900">{selectedLog.details?.city || 'N/A'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Zip")}</span>
                <div className="text-gray-900">{selectedLog.details?.zip || 'N/A'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Latitude")}</span>
                <div className="text-gray-900">{selectedLog.details?.lat || 'N/A'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Longitude")}</span>
                <div className="text-gray-900">{selectedLog.details?.lon || 'N/A'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Timezone")}</span>
                <div className="text-gray-900">{selectedLog.details?.timezone || 'N/A'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("ISP")}</span>
                <div className="text-gray-900">{selectedLog.details?.isp || 'N/A'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("IP")}</span>
                <div className="text-gray-900">{selectedLog.ip || selectedLog.details?.query ||  'N/A'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("AS")}</span>
                <div className="text-gray-900">{selectedLog.details?.as ||  'N/A'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Browser Name")}</span>
                <div className="text-gray-900">{selectedLog.details?.browser_name || 'Unknown'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Browser Language")}</span>
                <div className="text-gray-900">{selectedLog.details?.browser_language || 'en'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Os Name")}</span>
                <div className="text-gray-900">{selectedLog.details?.os_name || 'Unknown'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Device Type")}</span>
                <div className="text-gray-900">{selectedLog.details?.device_type || 'desktop'}</div>
              </div>
              <div>
                <span className="font-medium text-gray-600">{t("Referrer Host")}</span>
                <div className="text-gray-900">{selectedLog.details?.referrer_host || '-'}</div>
              </div>

              <div>
                <span className="font-medium text-gray-600">{t("Referrer Path")}</span>
                <div className="text-gray-900">{selectedLog.details?.referrer_path || '-'}</div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}
