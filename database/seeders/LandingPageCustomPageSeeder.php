<?php

namespace Database\Seeders;

use App\Models\LandingPageCustomPage;
use Illuminate\Database\Seeder;

class LandingPageCustomPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => "About Advocate SaaS Revolutionizing legal practice management with innovative legal solutions Empowering Legal Professionals Since 2020 Advocate SaaS is a leading legal practice management platform trusted by over 500 law firms worldwide. Our comprehensive suite includes case management, client portals, document automation, time tracking, and billing solutions designed specifically for legal professionals. Founded by former attorneys and legal technology experts, we understand the unique challenges facing modern law firms. Our mission is to streamline legal workflows, enhance client communication, and ensure regulatory compliance while maximizing profitability. - 500+ Law Firms - 50K+ Cases Managed - 99.9% Uptime SLA Our Core Values Security First: Bank-level encryption and compliance with legal industry standards Client-Centric: Enhancing attorney-client relationships through better communication",
                'meta_title' => 'About Us - Advocate SaaS Legal Practice Management',
                'meta_description' => 'Learn about Advocate SaaS, the leading platform trusted by 500+ law firms for comprehensive legal practice management solutions.',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "Privacy Policy Your privacy and client confidentiality are paramount. This policy explains how we protect your legal data with industry-leading security measures. Legal Data Protection We collect and protect the following information with bank-level encryption: - Case information and legal documents (AES-256 encrypted) - Client contact and billing information (PCI DSS compliant) - Attorney-client privileged communications (End-to-end encrypted) - Time tracking and billing records (SOC 2 Type II certified) Compliance & Security Our security measures include: - HIPAA and GDPR compliance for sensitive data - Multi-factor authentication and role-based access - Regular security audits and penetration testing - 24/7 security monitoring and incident response",
                'meta_title' => 'Privacy Policy - Advocate SaaS Legal Data Protection',
                'meta_description' => 'Read our comprehensive privacy policy detailing how Advocate SaaS protects legal data with bank-level encryption and industry compliance.',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => "Terms of Service Please read these terms carefully before using our comprehensive legal practice management services. Service Description Advocate SaaS provides comprehensive legal practice management services, including: - Case management and workflow automation - Secure client portal and communication tools - Document management with version control - Time tracking, billing, and payment processing - Court calendar integration and deadline management Professional Responsibilities As legal professionals, you are responsible for: - Maintaining attorney-client privilege and confidentiality - Ensuring compliance with local bar association rules - Proper case documentation and record keeping",
                'meta_title' => 'Terms of Service - Advocate SaaS Legal Platform',
                'meta_description' => 'Read our terms of service for Advocate SaaS legal practice management platform with 99.9% uptime guarantee.',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => "Contact Our Legal Technology Experts Need help transforming your legal practice? Get in touch with our specialized support team Get Expert Consultation Email Support: support@advocatesaas.com 24/7 legal practice support with 2-hour response time Phone Consultation: +1 234 567 890 Speak with legal technology specialists Schedule a Demo See how Advocate SaaS can transform your legal practice with a personalized demonstration. - 30 min Demo Duration - Free, No Cost",
                'meta_title' => 'Contact Us - Advocate SaaS Legal Technology Support',
                'meta_description' => 'Contact Advocate SaaS legal technology experts for 24/7 support, consultation, and personalized demos. Transform your practice today.',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => "Frequently Asked Questions Find answers to common questions about legal practice management and our platform Security & Compliance Is client data secure and compliant with legal standards? Yes, we maintain the highest security standards with AES-256 encryption, SOC 2 Type II certification, and compliance with HIPAA, GDPR, and state bar association requirements. How do you protect attorney-client privilege? Our platform is designed with legal privilege in mind. All communications are end-to-end encrypted, access is role-based, and we maintain detailed audit logs. Billing & Time Tracking Can I track billable hours and generate invoices? Absolutely! Our AI-powered time tracking captures billable hours automatically, categorizes activities, and generates professional invoices. Does it integrate with accounting software? Yes, Advocate SaaS integrates with QuickBooks, Xero, and other major accounting platforms.",
                'meta_title' => 'FAQ - Advocate SaaS Legal Practice Management Help',
                'meta_description' => 'Find comprehensive answers about security, billing, case management, and legal compliance for Advocate SaaS platform.',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'title' => 'Refund Policy',
                'slug' => 'refund-policy',
                'content' => "Refund Policy We stand behind our legal practice management platform with a comprehensive satisfaction guarantee and transparent refund policy. 30-Day Money Back Guarantee We offer a 30-day money back guarantee for all legal practice management plans. If Advocate SaaS doesn't meet your firm's needs, we'll refund your payment in full with no questions asked. Eligible Refunds Full refunds are available for: - Monthly and annual subscription plans (within 30 days) - Premium features and add-on modules - Setup and onboarding services Simple Refund Process 1. Contact Support: Email support@advocatesaas.com within 30 days 2. Quick Processing: We process refund requests within 24 hours 3. Receive Refund: Funds returned within 3-5 business days",
                'meta_title' => 'Refund Policy - Advocate SaaS 30-Day Guarantee',
                'meta_description' => 'Learn about our 30-day money-back guarantee and refund policy for Advocate SaaS legal practice management services.',
                'is_active' => true,
                'sort_order' => 6
            ]
        ];

        foreach ($pages as $pageData) {
            LandingPageCustomPage::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}
