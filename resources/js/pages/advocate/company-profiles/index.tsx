import { useState, useEffect, useRef, ReactNode } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Save,
  Edit,
  User,
  GraduationCap,
  Briefcase,
  Building2,
  Mail,
  Phone,
  Globe,
  MapPin,
  Award,
  Clock,
  Languages,
  TrendingUp,
  BadgeCheck,
  X,
} from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import { useInitials } from '@/hooks/use-initials';
import { formatCurrency } from '@/utils/helpers';

export default function CompanyProfiles() {
  const { t } = useTranslation();
  const { companyProfile } = usePage().props as any;
  const [isEditing, setIsEditing] = useState(!companyProfile);
  const [formData, setFormData] = useState({
    // Personal Details
    advocate_name: '',
    bar_registration_number: '',
    years_of_experience: '',

    // Contact Details
    email: '',
    phone: '',
    website: '',
    address: '',

    // Professional Details
    law_degree: '',
    university: '',
    specialization: '',

    // Court & Jurisdiction
    court_jurisdictions: '',
    languages_spoken: '',

    // Business Details
    consultation_fees: '',
    office_hours: '',
    success_rate: '',

    // Company Details
    name: '',
    registration_number: '',
    establishment_date: '',
    company_size: 'solo',
    business_type: 'law_firm',

    // Services
    services_offered: '',
    notable_cases: '',
    description: '',
    status: 'active'
  });

  useEffect(() => {
    if (companyProfile) {
      setFormData({
        advocate_name: companyProfile.advocate_name || '',
        bar_registration_number: companyProfile.bar_registration_number || '',
        years_of_experience: companyProfile.years_of_experience || '',
        email: companyProfile.email || '',
        phone: companyProfile.phone || '',
        website: companyProfile.website || '',
        address: companyProfile.address || '',
        law_degree: companyProfile.law_degree || '',
        university: companyProfile.university || '',
        specialization: companyProfile.specialization || '',
        court_jurisdictions: companyProfile.court_jurisdictions || '',
        languages_spoken: companyProfile.languages_spoken || '',
        consultation_fees: companyProfile.consultation_fees || '',
        office_hours: companyProfile.office_hours || '',
        success_rate: companyProfile.success_rate || '',
        name: companyProfile.name || '',
        registration_number: companyProfile.registration_number || '',
        establishment_date: companyProfile.establishment_date ? companyProfile.establishment_date.split('T')[0] : '',
        company_size: companyProfile.company_size || 'solo',
        business_type: companyProfile.business_type || 'law_firm',
        services_offered: companyProfile.services_offered || '',
        notable_cases: companyProfile.notable_cases || '',
        description: companyProfile.description || '',
        status: companyProfile.status || 'active'
      });
    }
  }, [companyProfile]);

  const handleChange = (name: string, value: any) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Ensure required fields have values
    const submitData = {
      ...formData,
      company_size: formData.company_size || 'solo',
      business_type: formData.business_type || 'law_firm'
    };

    if (companyProfile) {
      // Update existing profile
      toast.loading(t('Updating advocate profile...'));
      router.put(route('advocate.company-profiles.update', companyProfile?.id || 1), submitData, {
        onSuccess: () => {
          toast.dismiss();
          toast.success(t('Advocate profile updated successfully'));
          setIsEditing(false);
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'object' && errors !== null) {
            const errorMessages = Object.values(errors).flat().join(', ');
            toast.error(errorMessages);
          } else {
            toast.error(t('Failed to update advocate profile'));
          }
        }
      });
    } else {
      // Create new profile
      toast.loading(t('Creating advocate profile...'));
      router.post(route('advocate.company-profiles.store'), submitData, {
        onSuccess: () => {
          toast.dismiss();
          toast.success(t('Advocate profile created successfully'));
          setIsEditing(false);
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'object' && errors !== null) {
            const errorMessages = Object.values(errors).flat().join(', ');
            toast.error(errorMessages);
          } else {
            toast.error(t('Failed to create advocate profile'));
          }
        }
      });
    }
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Advocate'), href: route('advocate.company-profiles.index') },
    { title: t('Company Profiles') }
  ];

  const pageActions = [
    {
      label: isEditing ? t('Cancel') : t('Edit Profile'),
      icon: isEditing ? null : <Edit className="h-4 w-4 mr-2" />,
      variant: isEditing ? 'outline' : 'default',
      onClick: () => setIsEditing(!isEditing)
    }
  ];

  /* ── Presentation ───────────────────────────────────────── */

  const getInitials = useInitials();

  const sections = [
    { id: 'personal', label: t('Personal Information'), icon: User },
    { id: 'professional', label: t('Professional Details'), icon: GraduationCap },
    { id: 'business', label: t('Business Details'), icon: Briefcase },
    { id: 'firm', label: t('Firm Details'), icon: Building2 },
  ];

  const [activeSection, setActiveSection] = useState('personal');
  const sectionRefs = useRef<Record<string, HTMLDivElement | null>>({});

  const scrollToSection = (id: string) => {
    setActiveSection(id);
    sectionRefs.current[id]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // Profile completeness across the user-facing fields
  const trackedFields = [
    'advocate_name', 'bar_registration_number', 'years_of_experience', 'email', 'phone', 'website', 'address',
    'law_degree', 'university', 'specialization', 'court_jurisdictions', 'languages_spoken',
    'consultation_fees', 'office_hours', 'success_rate', 'name', 'registration_number',
    'establishment_date', 'services_offered', 'notable_cases', 'description',
  ] as const;
  const filledCount = trackedFields.filter((key) => String(formData[key] ?? '').trim() !== '').length;
  const completion = Math.round((filledCount / trackedFields.length) * 100);

  const companySizeLabels: Record<string, string> = {
    solo: t('Solo Practice'),
    small: t('Small Firm'),
    medium: t('Medium Firm'),
    large: t('Large Firm'),
  };

  const businessTypeLabels: Record<string, string> = {
    law_firm: t('Law Firm'),
    corporate_legal: t('Corporate Legal'),
    government: t('Government'),
    other: t('Other'),
  };

  const heroStats = [
    {
      label: t('Years of Experience'),
      value: formData.years_of_experience ? `${formData.years_of_experience}` : null,
      icon: Award,
    },
    {
      label: t('Success Rate (%)'),
      value: formData.success_rate ? `${formData.success_rate}%` : null,
      icon: TrendingUp,
    },
    {
      label: t('Consultation Fees'),
      value: formData.consultation_fees ? String(formatCurrency(Number(formData.consultation_fees) || 0)) : null,
      icon: Briefcase,
    },
    {
      label: t('Office Hours'),
      value: formData.office_hours || null,
      icon: Clock,
    },
  ].filter((stat) => stat.value);

  const contactChips = [
    { value: formData.email, icon: Mail },
    { value: formData.phone, icon: Phone },
    { value: formData.website, icon: Globe },
    { value: formData.languages_spoken, icon: Languages },
  ].filter((chip) => chip.value);

  const FieldShell = ({
    label,
    htmlFor,
    required,
    children,
    className,
  }: {
    label: string;
    htmlFor?: string;
    required?: boolean;
    children: ReactNode;
    className?: string;
  }) => (
    <div className={className}>
      <Label
        htmlFor={htmlFor}
        className="text-muted-foreground mb-1.5 block text-[11px] font-semibold tracking-[0.1em] uppercase"
      >
        {label}
        {required && <span className="text-rose-500"> *</span>}
      </Label>
      {children}
    </div>
  );

  const ReadOnly = ({ value, multiline = false }: { value?: string | number; multiline?: boolean }) => {
    const empty = value === null || value === undefined || String(value).trim() === '';
    return (
      <div
        className={cn(
          'bg-muted/40 flex min-h-11 items-center rounded-xl border px-3.5 py-2.5 text-sm',
          multiline && 'block whitespace-pre-wrap',
        )}
      >
        {empty ? <span className="text-muted-foreground/60">—</span> : value}
      </div>
    );
  };

  const inputClass = 'h-11 rounded-xl text-sm';
  const textareaClass = 'rounded-xl text-sm';

  const SectionCard = ({
    id,
    title,
    description,
    icon: Icon,
    tone,
    children,
  }: {
    id: string;
    title: string;
    description: string;
    icon: React.ElementType;
    tone: string;
    children: ReactNode;
  }) => (
    <div
      id={id}
      ref={(node) => {
        sectionRefs.current[id] = node;
      }}
      className="profile-in bg-card scroll-mt-6 overflow-hidden rounded-2xl border shadow-sm"
    >
      <div className="relative border-b p-5">
        <span className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-current to-transparent opacity-20" />
        <div className="flex items-start gap-3">
          <span
            className={cn(
              'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-lg',
              tone,
            )}
          >
            <Icon className="h-[18px] w-[18px]" />
          </span>
          <div>
            <h3 className="text-base font-semibold tracking-tight">{title}</h3>
            <p className="text-muted-foreground mt-0.5 text-sm">{description}</p>
          </div>
        </div>
      </div>
      <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">{children}</div>
    </div>
  );

  return (
    <PageTemplate
      title={t("Company Profiles")}
      url="/advocate/company-profiles"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Scoped animations */}
      <style>{`
        @keyframes profileFadeUp {
          from { opacity: 0; transform: translateY(14px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .profile-in { opacity: 0; animation: profileFadeUp .5s cubic-bezier(.16,.84,.44,1) forwards; }
        @media (prefers-reduced-motion: reduce) {
          .profile-in { animation: none; opacity: 1; }
        }
      `}</style>

      <form onSubmit={handleSubmit}>
        {/* ── Profile hero ─────────────────────────────────── */}
        <div className="profile-in bg-card relative overflow-hidden rounded-2xl border shadow-sm">
          <div className="from-primary/20 via-primary/[0.08] relative h-24 bg-gradient-to-br to-transparent">
            <div
              className="absolute inset-0 opacity-40"
              style={{
                backgroundImage: 'radial-gradient(circle at 20% 60%, rgba(255,255,255,.4) 1px, transparent 1px)',
                backgroundSize: '20px 20px',
              }}
            />
          </div>

          <div className="-mt-10 px-5 pb-5 sm:px-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
              <div className="flex items-end gap-4">
                <div className="from-primary to-primary/70 ring-card flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-2xl font-bold text-white shadow-xl ring-4">
                  {formData.advocate_name || formData.name ? (
                    getInitials(formData.advocate_name || formData.name)
                  ) : (
                    <User className="h-8 w-8" />
                  )}
                </div>
                <div className="min-w-0 pb-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <h2 className="truncate text-xl font-bold tracking-tight sm:text-2xl">
                      {formData.advocate_name || t('Advocate Name')}
                    </h2>
                    {formData.status === 'active' && (
                      <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 ring-inset dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/25">
                        <BadgeCheck className="h-3 w-3" />
                        {t('Active')}
                      </span>
                    )}
                  </div>
                  <p className="text-muted-foreground mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    {formData.name && <span className="font-medium">{formData.name}</span>}
                    {formData.name && <span className="opacity-40">·</span>}
                    <span>{businessTypeLabels[formData.business_type] || formData.business_type}</span>
                    <span className="opacity-40">·</span>
                    <span>{companySizeLabels[formData.company_size] || formData.company_size}</span>
                  </p>
                </div>
              </div>

              {/* Completion meter */}
              <div className="bg-muted/50 w-full rounded-2xl border p-3 sm:w-56">
                <div className="flex items-center justify-between gap-2">
                  <span className="text-muted-foreground text-[11px] font-semibold tracking-[0.1em] uppercase">
                    {t('Profile')}
                  </span>
                  <span className="text-sm font-bold tabular-nums">{completion}%</span>
                </div>
                <div className="bg-muted mt-2 h-1.5 w-full overflow-hidden rounded-full">
                  <div
                    className={cn(
                      'h-full rounded-full transition-[width] duration-1000 ease-out',
                      completion >= 80 ? 'bg-emerald-500' : completion >= 40 ? 'bg-primary' : 'bg-amber-500',
                    )}
                    style={{ width: `${completion}%` }}
                  />
                </div>
                <p className="text-muted-foreground mt-1.5 text-[11px] tabular-nums">
                  {filledCount} / {trackedFields.length} {t('fields')}
                </p>
              </div>
            </div>

            {/* Contact chips */}
            {contactChips.length > 0 && (
              <div className="mt-4 flex flex-wrap gap-2">
                {contactChips.map((chip, index) => {
                  const ChipIcon = chip.icon;
                  return (
                    <span
                      key={index}
                      className="bg-muted/50 text-muted-foreground inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs"
                    >
                      <ChipIcon className="h-3 w-3 shrink-0" />
                      <span className="truncate">{chip.value}</span>
                    </span>
                  );
                })}
                {formData.address && (
                  <span className="bg-muted/50 text-muted-foreground inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs">
                    <MapPin className="h-3 w-3 shrink-0" />
                    <span className="truncate">{formData.address}</span>
                  </span>
                )}
              </div>
            )}

            {/* Key stats */}
            {heroStats.length > 0 && (
              <div className="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                {heroStats.map((stat) => {
                  const StatIcon = stat.icon;
                  return (
                    <div key={stat.label} className="bg-muted/40 rounded-xl border p-3">
                      <div className="text-muted-foreground flex items-center gap-1.5 text-[11px] font-medium tracking-wide uppercase">
                        <StatIcon className="h-3 w-3 shrink-0" />
                        <span className="truncate">{stat.label}</span>
                      </div>
                      <div className="mt-1 truncate text-base font-bold tabular-nums">{stat.value}</div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* ── Section rail + panels ────────────────────────── */}
        <div className="mt-4 lg:grid lg:grid-cols-[210px_1fr] lg:gap-4">
          {/* Rail */}
          <div className="mb-4 lg:mb-0">
            <div className="lg:sticky lg:top-4">
              <div className="bg-card flex gap-2 overflow-x-auto rounded-2xl border p-2 shadow-sm lg:flex-col lg:overflow-visible">
                {sections.map((section) => {
                  const SectionIcon = section.icon;
                  const active = activeSection === section.id;
                  return (
                    <button
                      key={section.id}
                      type="button"
                      onClick={() => scrollToSection(section.id)}
                      className={cn(
                        'flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2.5 text-start text-[13px] font-medium transition-all duration-300 lg:w-full',
                        active
                          ? 'bg-primary/10 text-primary'
                          : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                      )}
                    >
                      <SectionIcon className="h-4 w-4 shrink-0" />
                      <span className="truncate">{section.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Panels */}
          <div className="space-y-4">
            {/* Personal Information */}
            <SectionCard
              id="personal"
              title={t('Personal Information')}
              description={t('Basic advocate details and contact information')}
              icon={User}
              tone="from-blue-500 to-indigo-600"
            >
              <FieldShell label={t('Advocate Name')} htmlFor="advocate_name" required>
                {isEditing ? (
                  <Input
                    id="advocate_name"
                    value={formData.advocate_name}
                    onChange={(e) => handleChange('advocate_name', e.target.value)}
                    required
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.advocate_name} />
                )}
              </FieldShell>

              <FieldShell label={t('Bar Registration Number')} htmlFor="bar_registration_number" required>
                {isEditing ? (
                  <Input
                    id="bar_registration_number"
                    value={formData.bar_registration_number}
                    onChange={(e) => handleChange('bar_registration_number', e.target.value)}
                    required
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.bar_registration_number} />
                )}
              </FieldShell>

              <FieldShell label={t('Email')} htmlFor="email">
                {isEditing ? (
                  <Input
                    id="email"
                    type="email"
                    value={formData.email}
                    onChange={(e) => handleChange('email', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.email} />
                )}
              </FieldShell>

              <FieldShell label={t('Phone')} htmlFor="phone">
                {isEditing ? (
                  <Input
                    id="phone"
                    value={formData.phone}
                    onChange={(e) => handleChange('phone', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.phone} />
                )}
              </FieldShell>

              <FieldShell label={t('Years of Experience')} htmlFor="years_of_experience">
                {isEditing ? (
                  <Input
                    id="years_of_experience"
                    type="number"
                    value={formData.years_of_experience}
                    onChange={(e) => handleChange('years_of_experience', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.years_of_experience} />
                )}
              </FieldShell>

              <FieldShell label={t('Website')} htmlFor="website">
                {isEditing ? (
                  <Input
                    id="website"
                    type="url"
                    value={formData.website}
                    onChange={(e) => handleChange('website', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.website} />
                )}
              </FieldShell>

              <FieldShell label={t('Address')} htmlFor="address" className="md:col-span-2">
                {isEditing ? (
                  <Textarea
                    id="address"
                    value={formData.address}
                    onChange={(e) => handleChange('address', e.target.value)}
                    rows={2}
                    className={textareaClass}
                  />
                ) : (
                  <ReadOnly value={formData.address} multiline />
                )}
              </FieldShell>
            </SectionCard>

            {/* Professional Details */}
            <SectionCard
              id="professional"
              title={t('Professional Details')}
              description={t('Educational background and specialization')}
              icon={GraduationCap}
              tone="from-violet-500 to-purple-600"
            >
              <FieldShell label={t('Law Degree')} htmlFor="law_degree">
                {isEditing ? (
                  <Input
                    id="law_degree"
                    value={formData.law_degree}
                    onChange={(e) => handleChange('law_degree', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.law_degree} />
                )}
              </FieldShell>

              <FieldShell label={t('University')} htmlFor="university">
                {isEditing ? (
                  <Input
                    id="university"
                    value={formData.university}
                    onChange={(e) => handleChange('university', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.university} />
                )}
              </FieldShell>

              <FieldShell label={t('Specialization')} htmlFor="specialization" className="md:col-span-2">
                {isEditing ? (
                  <Textarea
                    id="specialization"
                    value={formData.specialization}
                    onChange={(e) => handleChange('specialization', e.target.value)}
                    rows={2}
                    className={textareaClass}
                  />
                ) : (
                  <ReadOnly value={formData.specialization} multiline />
                )}
              </FieldShell>

              <FieldShell label={t('Court Jurisdictions')} htmlFor="court_jurisdictions">
                {isEditing ? (
                  <Textarea
                    id="court_jurisdictions"
                    value={formData.court_jurisdictions}
                    onChange={(e) => handleChange('court_jurisdictions', e.target.value)}
                    rows={2}
                    className={textareaClass}
                  />
                ) : (
                  <ReadOnly value={formData.court_jurisdictions} multiline />
                )}
              </FieldShell>

              <FieldShell label={t('Languages Spoken')} htmlFor="languages_spoken">
                {isEditing ? (
                  <Input
                    id="languages_spoken"
                    value={formData.languages_spoken}
                    onChange={(e) => handleChange('languages_spoken', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.languages_spoken} />
                )}
              </FieldShell>
            </SectionCard>

            {/* Business Details */}
            <SectionCard
              id="business"
              title={t('Business Details')}
              description={t('Practice and consultation information')}
              icon={Briefcase}
              tone="from-emerald-500 to-teal-600"
            >
              <FieldShell label={t('Consultation Fees')} htmlFor="consultation_fees">
                {isEditing ? (
                  <Input
                    id="consultation_fees"
                    type="number"
                    value={formData.consultation_fees}
                    onChange={(e) => handleChange('consultation_fees', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly
                    value={formData.consultation_fees ? String(formatCurrency(Number(formData.consultation_fees) || 0)) : ''}
                  />
                )}
              </FieldShell>

              <FieldShell label={t('Success Rate (%)')} htmlFor="success_rate">
                {isEditing ? (
                  <Input
                    id="success_rate"
                    type="number"
                    max="100"
                    value={formData.success_rate}
                    onChange={(e) => handleChange('success_rate', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.success_rate ? `${formData.success_rate}%` : ''} />
                )}
              </FieldShell>

              <FieldShell label={t('Office Hours')} htmlFor="office_hours">
                {isEditing ? (
                  <Input
                    id="office_hours"
                    value={formData.office_hours}
                    onChange={(e) => handleChange('office_hours', e.target.value)}
                    placeholder="e.g., Mon-Fri 9:00 AM - 6:00 PM"
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.office_hours} />
                )}
              </FieldShell>

              <FieldShell label={t('Practice Size')} htmlFor="company_size">
                {isEditing ? (
                  <Select
                    value={formData.company_size || 'solo'}
                    onValueChange={(value) => handleChange('company_size', value)}
                  >
                    <SelectTrigger className="h-11 rounded-xl text-sm">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="solo" className="text-sm">{t('Solo Practice')}</SelectItem>
                      <SelectItem value="small" className="text-sm">{t('Small Firm')}</SelectItem>
                      <SelectItem value="medium" className="text-sm">{t('Medium Firm')}</SelectItem>
                      <SelectItem value="large" className="text-sm">{t('Large Firm')}</SelectItem>
                    </SelectContent>
                  </Select>
                ) : (
                  <ReadOnly value={companySizeLabels[formData.company_size] || formData.company_size} />
                )}
              </FieldShell>

              <FieldShell label={t('Services Offered')} htmlFor="services_offered" className="md:col-span-2">
                {isEditing ? (
                  <Textarea
                    id="services_offered"
                    value={formData.services_offered}
                    onChange={(e) => handleChange('services_offered', e.target.value)}
                    rows={3}
                    className={textareaClass}
                  />
                ) : (
                  <ReadOnly value={formData.services_offered} multiline />
                )}
              </FieldShell>

              <FieldShell label={t('Notable Cases')} htmlFor="notable_cases" className="md:col-span-2">
                {isEditing ? (
                  <Textarea
                    id="notable_cases"
                    value={formData.notable_cases}
                    onChange={(e) => handleChange('notable_cases', e.target.value)}
                    rows={3}
                    className={textareaClass}
                  />
                ) : (
                  <ReadOnly value={formData.notable_cases} multiline />
                )}
              </FieldShell>
            </SectionCard>

            {/* Firm Details */}
            <SectionCard
              id="firm"
              title={t('Firm Details')}
              description={t('Legal firm registration and business information')}
              icon={Building2}
              tone="from-orange-500 to-amber-600"
            >
              <FieldShell label={t('Firm Name')} htmlFor="name">
                {isEditing ? (
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => handleChange('name', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.name} />
                )}
              </FieldShell>

              <FieldShell label={t('Registration Number')} htmlFor="registration_number">
                {isEditing ? (
                  <Input
                    id="registration_number"
                    value={formData.registration_number}
                    onChange={(e) => handleChange('registration_number', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.registration_number} />
                )}
              </FieldShell>

              <FieldShell label={t('Establishment Date')} htmlFor="establishment_date">
                {isEditing ? (
                  <Input
                    id="establishment_date"
                    type="date"
                    value={formData.establishment_date}
                    onChange={(e) => handleChange('establishment_date', e.target.value)}
                    className={inputClass}
                  />
                ) : (
                  <ReadOnly value={formData.establishment_date} />
                )}
              </FieldShell>

              <FieldShell label={t('Business Type')} htmlFor="business_type">
                {isEditing ? (
                  <Select
                    value={formData.business_type || 'law_firm'}
                    onValueChange={(value) => handleChange('business_type', value)}
                  >
                    <SelectTrigger className="h-11 rounded-xl text-sm">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="law_firm" className="text-sm">{t('Law Firm')}</SelectItem>
                      <SelectItem value="corporate_legal" className="text-sm">{t('Corporate Legal')}</SelectItem>
                      <SelectItem value="government" className="text-sm">{t('Government')}</SelectItem>
                      <SelectItem value="other" className="text-sm">{t('Other')}</SelectItem>
                    </SelectContent>
                  </Select>
                ) : (
                  <ReadOnly value={businessTypeLabels[formData.business_type] || formData.business_type} />
                )}
              </FieldShell>

              <FieldShell label={t('Description')} htmlFor="description" className="md:col-span-2">
                {isEditing ? (
                  <Textarea
                    id="description"
                    value={formData.description}
                    onChange={(e) => handleChange('description', e.target.value)}
                    rows={3}
                    className={textareaClass}
                  />
                ) : (
                  <ReadOnly value={formData.description} multiline />
                )}
              </FieldShell>
            </SectionCard>
          </div>
        </div>

        {/* Sticky save bar */}
        {isEditing && (
          <div className="sticky bottom-4 z-10 mt-4">
            <div className="bg-card/90 flex items-center justify-between gap-3 rounded-2xl border p-3 shadow-lg backdrop-blur">
              <p className="text-muted-foreground hidden text-xs sm:block">
                {companyProfile ? t('Unsaved changes to your profile') : t('Complete the required fields to create your profile')}
              </p>
              <div className="flex w-full items-center justify-end gap-2 sm:w-auto">
                {companyProfile && (
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => setIsEditing(false)}
                    className="h-10 rounded-xl"
                  >
                    <X className="mr-1.5 h-4 w-4" />
                    {t('Cancel')}
                  </Button>
                )}
                <Button type="submit" className="h-10 rounded-xl">
                  <Save className="mr-1.5 h-4 w-4" />
                  {companyProfile ? t('Update Profile') : t('Create Profile')}
                </Button>
              </div>
            </div>
          </div>
        )}
      </form>
    </PageTemplate>
  );
}
