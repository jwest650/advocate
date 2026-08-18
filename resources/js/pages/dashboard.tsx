import React from 'react';
import { PageTemplate } from '@/components/page-template';
import { RefreshCw, Scale, Users, Calendar, DollarSign, MessageSquare, Clock, TrendingUp, AlertTriangle, CheckCircle, Briefcase, Gavel, Shield, Timer, Target, BarChart3, ArrowUpRight, ChevronRight, Plus, Activity, HardDrive, Layers } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { Link, usePage } from '@inertiajs/react';
import { XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, ResponsiveContainer, PieChart, Pie, Cell, Area, AreaChart, RadialBarChart, RadialBar, PolarAngleAxis } from 'recharts';
import { formatCurrency } from '@/utils/helpers';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';

interface CompanyDashboardData {
  stats: {
    totalCases: number;
    activeCases: number;
    totalClients: number;
    totalRevenue: number;
    monthlyGrowth: number;
    pendingTasks: number;
    upcomingHearings: number;
    unreadMessages: number;
    activeClients?: number;
    currentUsers?: number;
  };
  recentActivity: Array<{
    id: number;
    type: 'case' | 'client' | 'hearing' | 'message' | 'task';
    title: string;
    description: string;
    time: string;
    status: 'success' | 'warning' | 'error' | 'info';
  }>;
  casesByStatus: Array<{ name: string; value: number; color: string }>;
  revenueData: Array<{ month: string; revenue: number; cases: number }>;
  upcomingHearings: Array<{
    id: number;
    title: string;
    court: string | { name?: string };
    date: string;
    time: string;
    type: string;
  }>;
  tasksPriority: Array<{ priority: string; count: number; color: string }>;
  plan: {
    name: string;
    storage_limit: number;
    max_users?: number;
    max_cases?: number;
    max_clients?: number;
    price?: number;
    yearly_price?: number;
    is_trial?: boolean;
    trial_expire_date?: string;
  };
  storage?: {
    total_used?: number;
  };
}

interface MetricDefinition {
  label: string;
  amount: number;
  format: (value: number) => string;
  caption: string;
  captionClass: string;
  captionIcon?: React.ElementType;
  icon: React.ElementType;
  href: string;
  accent: string;
  chip: string;
  ratio?: number;
  ratioLabel?: string;
}

interface PageAction {
  label: string;
  icon: React.ReactNode;
  variant: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  onClick: () => void;
}

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/* Eases a number up to its target on mount */
function useCountUp(target: number, duration = 1100) {
  const [value, setValue] = React.useState(() => (prefersReducedMotion() ? target : 0));

  React.useEffect(() => {
    if (prefersReducedMotion()) {
      setValue(target);
      return;
    }
    let frame = 0;
    const start = performance.now();
    const tick = (now: number) => {
      const progress = Math.min((now - start) / duration, 1);
      setValue(target * (1 - Math.pow(1 - progress, 3)));
      if (progress < 1) frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [target, duration]);

  return value;
}

/* Card shell with a gradient hairline border and a cursor-following spotlight */
function SpotlightCard({
  children,
  glow,
  className = '',
  spotlight = true,
}: {
  children: React.ReactNode;
  glow: string;
  className?: string;
  spotlight?: boolean;
}) {
  const ref = React.useRef<HTMLDivElement>(null);

  const handleMove = (event: React.MouseEvent<HTMLDivElement>) => {
    if (!spotlight || !ref.current) return;
    const rect = ref.current.getBoundingClientRect();
    ref.current.style.setProperty('--mx', `${event.clientX - rect.left}px`);
    ref.current.style.setProperty('--my', `${event.clientY - rect.top}px`);
  };

  return (
    <div
      ref={ref}
      onMouseMove={handleMove}
      className={`group/spot relative rounded-2xl p-px transition-transform duration-300 ${className}`}
      style={{ backgroundImage: `linear-gradient(160deg, ${glow}59, var(--border) 45%, var(--border))` }}
    >
      {spotlight && (
        <div
          className="pointer-events-none absolute inset-0 rounded-2xl opacity-0 transition-opacity duration-300 group-hover/spot:opacity-100"
          style={{ background: `radial-gradient(320px circle at var(--mx, 50%) var(--my, 0%), ${glow}24, transparent 70%)` }}
        />
      )}
      <div className="relative h-full rounded-[calc(1rem-1px)] bg-card">{children}</div>
    </div>
  );
}

function SectionLabel({ icon: Icon, title, action }: { icon: React.ElementType; title: string; action?: React.ReactNode }) {
  return (
    <div className="mb-3 flex items-center gap-3">
      <Icon className="h-3.5 w-3.5 text-muted-foreground" />
      <span className="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">{title}</span>
      <span className="h-px flex-1 bg-gradient-to-r from-border to-transparent" />
      {action}
    </div>
  );
}

function MetricTile({ metric, index }: { metric: MetricDefinition; index: number }) {
  const animated = useCountUp(metric.amount);
  const Icon = metric.icon;
  const CaptionIcon = metric.captionIcon;

  return (
    <Link href={metric.href} className="dash-in block" style={{ animationDelay: `${120 + index * 80}ms` }}>
      <SpotlightCard glow={metric.accent} className="h-full hover:-translate-y-1">
        <div className="relative h-full overflow-hidden rounded-[calc(1rem-1px)] p-5">
          {/* Ornamental corner wash */}
          <div
            className="pointer-events-none absolute -end-10 -top-12 h-32 w-32 rounded-full blur-2xl transition-opacity duration-500 group-hover/spot:opacity-90"
            style={{ background: `radial-gradient(circle, ${metric.accent}26, transparent 70%)`, opacity: 0.55 }}
          />

          <div className="relative flex items-start justify-between gap-3">
            <div
              className={`flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br text-white transition-transform duration-500 group-hover/spot:scale-110 group-hover/spot:rotate-[-6deg] ${metric.chip}`}
              style={{ boxShadow: `0 10px 24px -10px ${metric.accent}` }}
            >
              <Icon className="h-5 w-5" />
            </div>
            <ArrowUpRight className="h-4 w-4 shrink-0 text-muted-foreground opacity-0 transition-all duration-300 group-hover/spot:translate-x-0.5 group-hover/spot:-translate-y-0.5 group-hover/spot:opacity-100 rtl:-scale-x-100" />
          </div>

          <p className="relative mt-4 text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">{metric.label}</p>
          <h3 className="relative mt-1 text-[1.75rem] leading-none font-bold tracking-tight tabular-nums">
            {metric.format(animated)}
          </h3>

          <div className="relative mt-3 flex items-center gap-1.5 text-xs">
            <span className={`inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 font-medium ${metric.captionClass}`}>
              {CaptionIcon && <CaptionIcon className="h-3 w-3" />}
              {metric.caption}
            </span>
          </div>

          {typeof metric.ratio === 'number' && (
            <div className="relative mt-4">
              <div className="mb-1 flex items-center justify-between text-[11px] text-muted-foreground">
                <span>{metric.ratioLabel}</span>
                <span className="tabular-nums">{Math.round(metric.ratio)}%</span>
              </div>
              <div className="h-1 w-full overflow-hidden rounded-full bg-muted">
                <div
                  className="h-full rounded-full transition-[width] duration-1000 ease-out"
                  style={{
                    width: `${Math.max(0, Math.min(100, metric.ratio))}%`,
                    transitionDelay: `${300 + index * 80}ms`,
                    backgroundImage: `linear-gradient(90deg, ${metric.accent}99, ${metric.accent})`,
                  }}
                />
              </div>
            </div>
          )}
        </div>
      </SpotlightCard>
    </Link>
  );
}

export default function Dashboard({ dashboardData }: { dashboardData: CompanyDashboardData }) {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;
  const { themeColor, customColor } = useBrand();
  const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];

  // Drives the mount-in animation of the usage meters
  const [mounted, setMounted] = React.useState(false);
  React.useEffect(() => {
    const id = requestAnimationFrame(() => setMounted(true));
    return () => cancelAnimationFrame(id);
  }, []);

  const pageActions: PageAction[] = [
    {
      label: t('Analytics'),
      icon: <BarChart3 className="h-4 w-4" />,
      variant: 'outline',
      onClick: () => window.location.href = route('dashboard.analytics.index')
    },
    {
      label: t('Refresh'),
      icon: <RefreshCw className="h-4 w-4" />,
      variant: 'outline',
      onClick: () => window.location.reload()
    },
  ];

  const stats: CompanyDashboardData['stats'] = dashboardData?.stats || {
    totalCases: 156,
    activeCases: 89,
    totalClients: 234,
    totalRevenue: 125000,
    monthlyGrowth: 12.5,
    pendingTasks: 23,
    upcomingHearings: 8,
    unreadMessages: 15
  };

  const recentActivity = dashboardData?.recentActivity || [];
  const casesByStatus = dashboardData?.casesByStatus || [
    { name: 'Active', value: 45, color: '#10b77f' },
    { name: 'Pending', value: 25, color: '#f59e0b' },
    { name: 'Closed', value: 30, color: '#6b7280' }
  ];
  const revenueData = dashboardData?.revenueData || [];
  const tasksPriority = dashboardData?.tasksPriority || [
    { priority: 'High', count: 8, color: '#ef4444' },
    { priority: 'Medium', count: 12, color: '#f59e0b' },
    { priority: 'Low', count: 3, color: '#10b77f' }
  ];
  const upcomingHearings = dashboardData?.upcomingHearings || [];

  const metrics: MetricDefinition[] = [
    {
      label: t('Active Cases'),
      amount: Number(stats.activeCases) || 0,
      format: (value: number) => String(Math.round(value)),
      caption: `${stats.totalCases} ${t('total')}`,
      captionClass: 'bg-muted text-muted-foreground',
      icon: Scale,
      href: route('cases.index'),
      accent: '#3b82f6',
      chip: 'from-blue-500 to-blue-600',
      ratio: stats.totalCases ? (Number(stats.activeCases) / Number(stats.totalCases)) * 100 : undefined,
      ratioLabel: t('Active share'),
    },
    {
      label: t('Active Clients'),
      amount: Number(stats.activeClients || stats.totalClients) || 0,
      format: (value: number) => String(Math.round(value)),
      caption: `+${stats.monthlyGrowth}% ${t('this month')}`,
      captionClass: 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400',
      captionIcon: TrendingUp,
      icon: Users,
      href: route('clients.index'),
      accent: '#22c55e',
      chip: 'from-green-500 to-emerald-600',
    },
    {
      label: t('Total Revenue'),
      amount: Number(stats?.totalRevenue ?? 0) || 0,
      format: (value: number) => String(formatCurrency(Math.round(value))),
      caption: t('This year'),
      captionClass: 'bg-muted text-muted-foreground',
      icon: DollarSign,
      href: route('clients.billing.index'),
      accent: '#10b981',
      chip: 'from-emerald-500 to-teal-600',
    },
    {
      label: t('Pending Tasks'),
      amount: Number(stats.pendingTasks) || 0,
      format: (value: number) => String(Math.round(value)),
      caption: `${stats.upcomingHearings} ${t('hearings due')}`,
      captionClass: 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
      captionIcon: Timer,
      icon: Clock,
      href: route('tasks.index'),
      accent: '#f97316',
      chip: 'from-orange-500 to-amber-600',
    },
  ];

  const quickActions = [
    { label: t('New Case'), icon: Scale, href: route('cases.index') },
    { label: t('Add Client'), icon: Users, href: route('clients.index') },
    { label: t('Schedule Hearing'), icon: Gavel, href: route('hearings.index') },
    { label: t('Messages'), icon: MessageSquare, href: route('communication.messages.index'), badge: stats.unreadMessages },
  ];

  const tasksTotal = tasksPriority.reduce((sum, task) => sum + (task.count || 0), 0);
  const casesTotal = casesByStatus.reduce((sum, entry) => sum + (entry.value || 0), 0);
  const initials = String(auth?.user?.name || '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part: string) => part[0]?.toUpperCase())
    .join('');

  /* Splits a hearing date into a day/month tile, falling back when unparseable */
  const hearingDateParts = (value: string) => {
    const parsed = new Date(value);
    if (!value || Number.isNaN(parsed.getTime())) return null;
    return {
      day: parsed.getDate(),
      month: parsed.toLocaleDateString(undefined, { month: 'short' }),
    };
  };

  // ShareModal state variables removed



  return (
    <PageTemplate
      title={t('Dashboard')}
      url="/dashboard"
      actions={pageActions}
      noPadding
    >
      {/* Scoped animations for the dashboard */}
      <style>{`
        @keyframes dashFadeUp {
          from { opacity: 0; transform: translateY(16px); }
          to { opacity: 1; transform: translateY(0); }
        }
        @keyframes dashDrift {
          0%   { transform: translate(0, 0) scale(1); }
          50%  { transform: translate(-5%, 7%) scale(1.12); }
          100% { transform: translate(0, 0) scale(1); }
        }
        @keyframes dashPulse {
          0%, 100% { opacity: .5; transform: scale(1); }
          50% { opacity: 1; transform: scale(1.35); }
        }
        .dash-in { opacity: 0; animation: dashFadeUp .55s cubic-bezier(.16,.84,.44,1) forwards; }
        .dash-drift { animation: dashDrift 18s ease-in-out infinite; }
        .dash-ping { animation: dashPulse 2.4s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) {
          .dash-in { animation: none; opacity: 1; }
          .dash-drift, .dash-ping { animation: none; }
        }
      `}</style>

      <div className="space-y-8">
        {/* ── Hero ─────────────────────────────────────────────── */}
        <div
          className="dash-in relative overflow-hidden rounded-3xl p-6 text-white shadow-2xl sm:p-8"
          style={{ backgroundColor: '#0b1120' }}
        >
          {/* Brand mesh */}
          <div
            className="pointer-events-none absolute inset-0"
            style={{
              backgroundImage: `radial-gradient(120% 120% at 100% 0%, ${primaryColor}59 0%, transparent 55%), radial-gradient(90% 90% at 0% 100%, ${primaryColor}33 0%, transparent 60%)`,
            }}
          />
          <div
            className="dash-drift pointer-events-none absolute -top-28 end-1/4 h-72 w-72 rounded-full blur-3xl"
            style={{ background: `radial-gradient(circle, ${primaryColor}6b, transparent 70%)` }}
          />
          {/* Grid texture */}
          <div
            className="pointer-events-none absolute inset-0 opacity-[0.14]"
            style={{
              backgroundImage:
                'linear-gradient(to right, rgba(255,255,255,.5) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.5) 1px, transparent 1px)',
              backgroundSize: '44px 44px',
              maskImage: 'radial-gradient(ellipse 80% 100% at 50% 0%, black, transparent 75%)',
              WebkitMaskImage: 'radial-gradient(ellipse 80% 100% at 50% 0%, black, transparent 75%)',
            }}
          />

          <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex items-start gap-4">
              <div
                className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-lg font-bold text-white ring-1 ring-white/20"
                style={{ backgroundImage: `linear-gradient(140deg, ${primaryColor}, ${primaryColor}80)` }}
              >
                {initials || <Users className="h-6 w-6" />}
              </div>
              <div>
                <div className="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-medium text-white/80 ring-1 ring-white/15 backdrop-blur">
                  <span className="relative flex h-1.5 w-1.5">
                    <span className="dash-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400" />
                    <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400" />
                  </span>
                  {dashboardData?.plan?.name || 'Free Plan'}
                </div>
                <h2 className="text-2xl font-bold tracking-tight sm:text-[1.75rem]">
                  {t('Welcome back')}{auth?.user?.name ? `, ${auth.user.name}` : ''}
                </h2>
                <p className="mt-1 text-sm text-white/60">
                  {new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}
                </p>
              </div>
            </div>

            {/* At-a-glance strip */}
            <div className="flex flex-wrap items-stretch gap-3">
              <Link href={route('hearings.index')} className="group flex-1">
                <div className="h-full min-w-[124px] rounded-2xl bg-white/[0.07] p-3.5 ring-1 ring-white/10 backdrop-blur transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-white/[0.12]">
                  <div className="flex items-center gap-2 text-[11px] font-medium tracking-wide text-white/60 uppercase">
                    <Gavel className="h-3 w-3" />
                    {t('Hearings')}
                  </div>
                  <div className="mt-1.5 text-2xl leading-none font-bold tabular-nums">{stats.upcomingHearings}</div>
                </div>
              </Link>
              <Link href={route('communication.messages.index')} className="group flex-1">
                <div className="h-full min-w-[124px] rounded-2xl bg-white/[0.07] p-3.5 ring-1 ring-white/10 backdrop-blur transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-white/[0.12]">
                  <div className="flex items-center gap-2 text-[11px] font-medium tracking-wide text-white/60 uppercase">
                    <MessageSquare className="h-3 w-3" />
                    {t('Messages')}
                  </div>
                  <div className="mt-1.5 text-2xl leading-none font-bold tabular-nums">{stats.unreadMessages}</div>
                </div>
              </Link>
              <div className="flex flex-col gap-2">
                <Link href={route('cases.index')}>
                  <button
                    type="button"
                    className="group inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                    style={{ backgroundColor: primaryColor, boxShadow: `0 10px 26px -10px ${primaryColor}` }}
                  >
                    <Plus className="h-4 w-4 transition-transform duration-300 group-hover:rotate-90" />
                    {t('New Case')}
                  </button>
                </Link>
                <Link href={route('dashboard.analytics.index')}>
                  <button
                    type="button"
                    className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-medium text-white ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/[0.16]"
                  >
                    <BarChart3 className="h-4 w-4" />
                    {t('Analytics')}
                  </button>
                </Link>
              </div>
            </div>
          </div>
        </div>

        {/* ── Overview ─────────────────────────────────────────── */}
        <div>
          <SectionLabel icon={Activity} title={t('Overview')} />
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {metrics.map((metric, index) => (
              <MetricTile key={metric.label} metric={metric} index={index} />
            ))}
          </div>
        </div>

        {/* ── Performance ──────────────────────────────────────── */}
        {(revenueData.length > 0 || casesTotal > 0) && (
          <div>
            <SectionLabel icon={TrendingUp} title={t('Performance')} />
            <div className="grid gap-4 lg:grid-cols-3">
              {revenueData.length > 0 && (
                <SpotlightCard glow={primaryColor} className="dash-in lg:col-span-2" spotlight={false}>
                  <div className="p-5 sm:p-6">
                    <div className="mb-5 flex items-start justify-between gap-3">
                      <div>
                        <p className="text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">{t('Revenue')}</p>
                        <h3 className="mt-1.5 text-2xl font-bold tracking-tight tabular-nums">{formatCurrency(stats?.totalRevenue ?? 0)}</h3>
                      </div>
                      <Badge
                        variant="outline"
                        className="shrink-0 gap-1 rounded-full border-green-200 bg-green-50 font-medium text-green-700 dark:border-green-500/25 dark:bg-green-500/10 dark:text-green-400"
                      >
                        <TrendingUp className="h-3 w-3" />
                        +{stats.monthlyGrowth}%
                      </Badge>
                    </div>
                    <div className="h-[248px] w-full">
                      <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={revenueData} margin={{ top: 6, right: 6, left: 0, bottom: 0 }}>
                          <defs>
                            <linearGradient id="dashRevenueFill" x1="0" y1="0" x2="0" y2="1">
                              <stop offset="0%" stopColor={primaryColor} stopOpacity={0.4} />
                              <stop offset="100%" stopColor={primaryColor} stopOpacity={0} />
                            </linearGradient>
                          </defs>
                          <CartesianGrid strokeDasharray="4 4" vertical={false} stroke="var(--border)" />
                          <XAxis dataKey="month" tickLine={false} axisLine={false} tickMargin={10} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} />
                          <YAxis tickLine={false} axisLine={false} width={44} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} />
                          <RechartsTooltip
                            cursor={{ stroke: primaryColor, strokeWidth: 1, strokeDasharray: '4 4' }}
                            contentStyle={{
                              borderRadius: '0.875rem',
                              border: '1px solid var(--border)',
                              background: 'var(--card)',
                              color: 'var(--card-foreground)',
                              fontSize: 12,
                              boxShadow: '0 16px 40px -16px rgba(15,23,42,.35)',
                            }}
                            formatter={(value: number | string | Array<number | string>, name: number | string) => [
                              String(name) === 'revenue' ? formatCurrency(Number(value) || 0) : value,
                              t(String(name) === 'revenue' ? 'Revenue' : 'Cases'),
                            ]}
                          />
                          <Area
                            type="monotone"
                            dataKey="revenue"
                            stroke={primaryColor}
                            strokeWidth={2.5}
                            fill="url(#dashRevenueFill)"
                            dot={false}
                            activeDot={{ r: 5, strokeWidth: 3, stroke: 'var(--card)' }}
                          />
                        </AreaChart>
                      </ResponsiveContainer>
                    </div>
                  </div>
                </SpotlightCard>
              )}

              {casesTotal > 0 && (
                <SpotlightCard
                  glow={primaryColor}
                  className={`dash-in ${revenueData.length > 0 ? '' : 'lg:col-span-3'}`}
                  spotlight={false}
                >
                  <div className="p-5 sm:p-6">
                    <p className="text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">{t('Cases by Status')}</p>
                    <div className="relative mt-2 h-[168px] w-full">
                      <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                          <Pie
                            data={casesByStatus}
                            dataKey="value"
                            nameKey="name"
                            innerRadius={56}
                            outerRadius={78}
                            paddingAngle={4}
                            cornerRadius={6}
                            stroke="none"
                          >
                            {casesByStatus.map((entry, index) => (
                              <Cell key={index} fill={entry.color} />
                            ))}
                          </Pie>
                          <RechartsTooltip
                            contentStyle={{
                              borderRadius: '0.875rem',
                              border: '1px solid var(--border)',
                              background: 'var(--card)',
                              color: 'var(--card-foreground)',
                              fontSize: 12,
                            }}
                          />
                        </PieChart>
                      </ResponsiveContainer>
                      <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span className="text-2xl leading-none font-bold tabular-nums">{casesTotal}</span>
                        <span className="mt-1 text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase">{t('Cases')}</span>
                      </div>
                    </div>
                    <div className="mt-4 space-y-2">
                      {casesByStatus.map((entry, index) => (
                        <div key={index} className="flex items-center gap-2.5 text-sm">
                          <span className="h-2 w-2 shrink-0 rounded-full" style={{ backgroundColor: entry.color }} />
                          <span className="flex-1 truncate text-muted-foreground">{t(entry.name)}</span>
                          <span className="font-semibold tabular-nums">{entry.value}</span>
                          <span className="w-10 text-end text-xs text-muted-foreground tabular-nums">
                            {Math.round(((entry.value || 0) / casesTotal) * 100)}%
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </SpotlightCard>
              )}
            </div>
          </div>
        )}

        {/* ── Operations ───────────────────────────────────────── */}
        <div>
          <SectionLabel icon={Layers} title={t('Operations')} />
          <div className="grid gap-4 lg:grid-cols-2">
            {/* Upcoming Hearings */}
            <SpotlightCard glow="#a855f7" className="dash-in" spotlight={false}>
              <div className="flex h-full flex-col p-5 sm:p-6">
                <div className="mb-4 flex items-center justify-between gap-3">
                  <div className="flex items-center gap-2.5">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg shadow-purple-500/25">
                      <Gavel className="h-4 w-4" />
                    </span>
                    <h3 className="font-semibold tracking-tight">{t('Upcoming Hearings')}</h3>
                  </div>
                  <Link href={route('hearings.index')} className="group inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground">
                    {t('View all')}
                    <ChevronRight className="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5 rtl:rotate-180" />
                  </Link>
                </div>

                <div className="-me-2 max-h-[336px] flex-1 space-y-2 overflow-y-auto pe-2">
                  {upcomingHearings.map((hearing, index) => {
                    const parts = hearingDateParts(hearing.date);
                    return (
                      <div
                        key={hearing.id}
                        className="dash-in group relative flex items-center gap-3 overflow-hidden rounded-xl border border-border/60 bg-card p-2.5 transition-all duration-300 hover:border-transparent hover:bg-muted/40 hover:shadow-md"
                        style={{ animationDelay: `${index * 60}ms` }}
                      >
                        <span className="absolute inset-y-0 start-0 w-0 bg-purple-500 transition-all duration-300 group-hover:w-[3px]" />
                        <div className="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-purple-50 text-purple-700 transition-transform duration-300 group-hover:scale-105 dark:bg-purple-500/15 dark:text-purple-300">
                          {parts ? (
                            <>
                              <span className="text-sm leading-none font-bold tabular-nums">{parts.day}</span>
                              <span className="mt-0.5 text-[9px] font-semibold tracking-wide uppercase opacity-80">{parts.month}</span>
                            </>
                          ) : (
                            <Calendar className="h-4 w-4" />
                          )}
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-semibold">{hearing.title}</p>
                          <p className="truncate text-xs text-muted-foreground">
                            {typeof hearing.court === 'string' ? hearing.court : hearing.court?.name}
                          </p>
                          <p className="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground tabular-nums">
                            <Clock className="h-3 w-3" />
                            {hearing.date} · {hearing.time}
                          </p>
                        </div>
                        <Badge variant="secondary" className="shrink-0 rounded-full text-[11px] font-medium">{hearing.type}</Badge>
                      </div>
                    );
                  })}
                  {upcomingHearings.length === 0 && (
                    <div className="flex flex-col items-center justify-center py-14 text-center">
                      <div className="relative mb-4">
                        <div className="absolute inset-0 rounded-2xl blur-xl" style={{ background: `${primaryColor}26` }} />
                        <div className="relative flex h-14 w-14 items-center justify-center rounded-2xl border bg-card">
                          <Calendar className="h-6 w-6 text-muted-foreground" />
                        </div>
                      </div>
                      <p className="text-sm font-medium">{t('No upcoming hearings')}</p>
                      <Link href={route('hearings.index')} className="mt-3">
                        <Button variant="outline" size="sm" className="rounded-full">
                          <Plus className="me-1 h-3.5 w-3.5" />
                          {t('Schedule Hearing')}
                        </Button>
                      </Link>
                    </div>
                  )}
                </div>
              </div>
            </SpotlightCard>

            {/* Plan Status */}
            <SpotlightCard glow={primaryColor} className="dash-in" spotlight={false}>
              <div className="p-5 sm:p-6">
                {(() => {
                  const plan: Partial<CompanyDashboardData['plan']> = dashboardData?.plan || {};
                  const stats: Partial<CompanyDashboardData['stats']> = dashboardData?.stats || {};
                  const maxUsers = plan.max_users || 5;
                  const storageLimit = plan.storage_limit || 5;
                  const totalStorageUsed = dashboardData?.storage?.total_used || 0;
                  const currentUsers = stats.currentUsers || 0;

                  const meters = [
                    {
                      label: t('Team Members'),
                      detail: `${currentUsers} / ${maxUsers}`,
                      percent: (currentUsers / maxUsers) * 100,
                      icon: Users,
                    },
                    {
                      label: t('Storage'),
                      detail: `${totalStorageUsed} GB / ${storageLimit} GB`,
                      percent: (totalStorageUsed / storageLimit) * 100,
                      icon: HardDrive,
                    },
                    {
                      label: t('Cases'),
                      detail: `${stats.totalCases || 0} / ${plan.max_cases || '∞'}`,
                      percent: plan.max_cases ? Math.min((stats.totalCases || 0) / plan.max_cases, 1) * 100 : 50,
                      icon: Scale,
                    },
                    {
                      label: t('Clients'),
                      detail: `${stats.totalClients || 0} / ${plan.max_clients || '∞'}`,
                      percent: plan.max_clients ? Math.min((stats.totalClients || 0) / plan.max_clients, 1) * 100 : 50,
                      icon: Briefcase,
                    },
                  ];

                  const rings = meters
                    .map((meter, index) => ({
                      name: meter.label,
                      percent: Math.max(0, Math.min(100, Number(meter.percent) || 0)),
                      fill: index === 0 ? primaryColor : `${primaryColor}${['ff', 'cc', '99', '66'][index]}`,
                    }))
                    .reverse();

                  return (
                    <>
                      <div className="mb-4 flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                          <span
                            className="flex h-9 w-9 items-center justify-center rounded-xl text-white"
                            style={{ backgroundImage: `linear-gradient(140deg, ${primaryColor}, ${primaryColor}b3)`, boxShadow: `0 10px 24px -10px ${primaryColor}` }}
                          >
                            <Target className="h-4 w-4" />
                          </span>
                          <h3 className="font-semibold tracking-tight">{t('Plan Status')}</h3>
                        </div>
                        <Badge
                          variant="outline"
                          className="rounded-full font-semibold"
                          style={{ borderColor: `${primaryColor}4d`, color: primaryColor, backgroundColor: `${primaryColor}0f` }}
                        >
                          {plan.name || 'Free Plan'}
                        </Badge>
                      </div>

                      <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                        {/* Concentric usage rings */}
                        <div className="relative h-[164px] w-[164px] shrink-0">
                          <ResponsiveContainer width="100%" height="100%">
                            <RadialBarChart
                              data={rings}
                              innerRadius="34%"
                              outerRadius="100%"
                              startAngle={90}
                              endAngle={-270}
                              barSize={9}
                            >
                              <PolarAngleAxis type="number" domain={[0, 100]} tick={false} axisLine={false} />
                              <RadialBar
                                dataKey="percent"
                                cornerRadius={6}
                                background={{ fill: 'var(--muted)' }}
                                isAnimationActive={!prefersReducedMotion()}
                                animationDuration={1200}
                              />
                              <RechartsTooltip
                                contentStyle={{
                                  borderRadius: '0.875rem',
                                  border: '1px solid var(--border)',
                                  background: 'var(--card)',
                                  color: 'var(--card-foreground)',
                                  fontSize: 12,
                                }}
                                formatter={(value: number | string | Array<number | string>) => [`${Math.round(Number(value) || 0)}%`, t('Used')]}
                              />
                            </RadialBarChart>
                          </ResponsiveContainer>
                          <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase">{t('Usage')}</span>
                          </div>
                        </div>

                        {/* Meter legend */}
                        <div className="w-full flex-1 space-y-2.5">
                          {meters.map((meter, index) => {
                            const MeterIcon = meter.icon;
                            const percent = Math.max(0, Math.min(100, Number(meter.percent) || 0));
                            return (
                              <div key={meter.label} className="rounded-xl border border-border/60 p-2.5 transition-colors duration-300 hover:bg-muted/40">
                                <div className="flex items-center justify-between gap-2">
                                  <span className="flex min-w-0 items-center gap-2 text-[13px] font-medium">
                                    <MeterIcon className="h-3.5 w-3.5 shrink-0" style={{ color: primaryColor }} />
                                    <span className="truncate">{meter.label}</span>
                                  </span>
                                  <span className="shrink-0 text-xs text-muted-foreground tabular-nums">{meter.detail}</span>
                                </div>
                                <div className="mt-2 h-1 w-full overflow-hidden rounded-full bg-muted">
                                  <div
                                    className="h-full rounded-full transition-[width] duration-1000 ease-out"
                                    style={{
                                      width: mounted ? `${percent}%` : '0%',
                                      transitionDelay: `${index * 120}ms`,
                                      backgroundImage: `linear-gradient(90deg, ${primaryColor}80, ${primaryColor})`,
                                    }}
                                  />
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </div>

                      <div className="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-muted/50 p-3">
                        <div className="flex items-center gap-2 text-sm font-medium">
                          <Shield className="h-4 w-4" style={{ color: primaryColor }} />
                          {t('Plan Details')}
                        </div>
                        <div className="text-end">
                          <div className="text-base font-bold tabular-nums">{formatCurrency(plan.price || 0)}<span className="text-xs font-normal text-muted-foreground">/mo</span></div>
                          {plan.yearly_price && (
                            <div className="text-xs text-muted-foreground tabular-nums">{formatCurrency(plan.yearly_price)}/yr</div>
                          )}
                        </div>
                      </div>

                      {plan.is_trial && plan.trial_expire_date && (
                        <div className="mt-3 flex items-start gap-2 rounded-xl border border-orange-200 bg-orange-50 p-2.5 text-xs text-orange-700 dark:border-orange-500/25 dark:bg-orange-500/10 dark:text-orange-300">
                          <AlertTriangle className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                          <span>
                            {t('Trial expires')}:{' '} {plan?.trial_expire_date ? new Date(plan.trial_expire_date).toLocaleDateString() : '-'}
                          </span>
                        </div>
                      )}
                    </>
                  );
                })()}
              </div>
            </SpotlightCard>
          </div>
        </div>

        {/* ── Workload & actions ───────────────────────────────── */}
        <div>
          <SectionLabel icon={Target} title={t('Workload')} />
          <div className="grid gap-4 lg:grid-cols-2">
            {/* Tasks by Priority */}
            <SpotlightCard glow="#ef4444" className="dash-in" spotlight={false}>
              <div className="p-5 sm:p-6">
                <div className="mb-5 flex items-start justify-between gap-3">
                  <div className="flex items-center gap-2.5">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/25">
                      <Target className="h-4 w-4" />
                    </span>
                    <h3 className="font-semibold tracking-tight">{t('Tasks by Priority')}</h3>
                  </div>
                  <div className="text-end">
                    <div className="text-xl leading-none font-bold tabular-nums">{tasksTotal}</div>
                    <div className="mt-1 text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase">{t('tasks')}</div>
                  </div>
                </div>

                {/* Single stacked distribution bar */}
                {tasksTotal > 0 && (
                  <div className="mb-5 flex h-2.5 w-full gap-1 overflow-hidden rounded-full">
                    {tasksPriority.map((task, index) => (
                      <div
                        key={index}
                        className="h-full rounded-full transition-[width] duration-1000 ease-out"
                        title={`${task.priority}: ${task.count}`}
                        style={{
                          width: mounted ? `${((task.count || 0) / tasksTotal) * 100}%` : '0%',
                          transitionDelay: `${index * 120}ms`,
                          backgroundColor: task.color,
                        }}
                      />
                    ))}
                  </div>
                )}

                <div className="space-y-2">
                  {tasksPriority.map((task, index) => (
                    <div
                      key={index}
                      className="group flex items-center gap-3 rounded-xl border border-border/60 p-3 transition-all duration-300 hover:border-transparent hover:bg-muted/40"
                    >
                      <span
                        className="h-2.5 w-2.5 shrink-0 rounded-full transition-transform duration-300 group-hover:scale-125"
                        style={{ backgroundColor: task.color, boxShadow: `0 0 0 4px ${task.color}1f` }}
                      />
                      <span className="flex-1 truncate text-sm font-medium">{task.priority} Priority</span>
                      <span className="text-xs text-muted-foreground tabular-nums">
                        {tasksTotal > 0 ? Math.round(((task.count || 0) / tasksTotal) * 100) : 0}%
                      </span>
                      <span className="w-9 text-end text-lg font-bold tabular-nums">{task.count}</span>
                    </div>
                  ))}
                  {tasksPriority.length === 0 && (
                    <div className="py-10 text-center">
                      <CheckCircle className="mx-auto mb-2 h-10 w-10 text-muted-foreground opacity-50" />
                      <p className="text-sm text-muted-foreground">{t('No tasks')}</p>
                    </div>
                  )}
                </div>
              </div>
            </SpotlightCard>

            {/* Quick Actions */}
            <SpotlightCard glow={primaryColor} className="dash-in">
              <div className="p-5 sm:p-6">
                <div className="mb-5 flex items-center gap-2.5">
                  <span
                    className="flex h-9 w-9 items-center justify-center rounded-xl text-white"
                    style={{ backgroundImage: `linear-gradient(140deg, ${primaryColor}, ${primaryColor}b3)`, boxShadow: `0 10px 24px -10px ${primaryColor}` }}
                  >
                    <Shield className="h-4 w-4" />
                  </span>
                  <h3 className="font-semibold tracking-tight">{t('Quick Actions')}</h3>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                  {quickActions.map((action) => {
                    const ActionIcon = action.icon;
                    return (
                      <Link key={action.label} href={action.href} className="group/qa block">
                        <div className="relative h-full overflow-hidden rounded-xl border border-border/60 p-4 transition-all duration-300 group-hover/qa:-translate-y-1 group-hover/qa:border-transparent group-hover/qa:shadow-lg">
                          <div
                            className="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover/qa:opacity-100"
                            style={{ backgroundImage: `linear-gradient(140deg, ${primaryColor}1f, transparent 70%)` }}
                          />
                          <div className="relative flex items-start justify-between">
                            <span
                              className="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-300 group-hover/qa:scale-110 group-hover/qa:rotate-[-6deg]"
                              style={{ backgroundColor: `${primaryColor}1a`, color: primaryColor }}
                            >
                              <ActionIcon className="h-[18px] w-[18px]" />
                            </span>
                            {action.badge ? (
                              <Badge className="h-5 min-w-5 rounded-full px-1.5 text-[11px] tabular-nums">{action.badge}</Badge>
                            ) : (
                              <ArrowUpRight className="h-4 w-4 text-muted-foreground opacity-0 transition-all duration-300 group-hover/qa:translate-x-0.5 group-hover/qa:-translate-y-0.5 group-hover/qa:opacity-100 rtl:-scale-x-100" />
                            )}
                          </div>
                          <p className="relative mt-3 text-sm font-semibold">{action.label}</p>
                        </div>
                      </Link>
                    );
                  })}
                </div>
              </div>
            </SpotlightCard>
          </div>
        </div>
      </div>

      {/* Share Modal removed */}
    </PageTemplate>
  );
}
