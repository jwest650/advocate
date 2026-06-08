<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingPageSetting;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $settings = LandingPageSetting::first();
        
        if ($settings) {
            $configSections = $settings->config_sections;
            
            // Find FAQ section and update it
            if (isset($configSections['sections'])) {
                foreach ($configSections['sections'] as &$section) {
                    if ($section['key'] === 'faq') {
                        $section['faqs'] = [
                            [
                                'question' => 'What is a legal case management system?',
                                'answer' => 'A legal case management system is a comprehensive software solution that helps law firms and legal professionals organize, track, and manage their cases, clients, documents, deadlines, and billing in one centralized platform.'
                            ],
                            [
                                'question' => 'Is client data secure and confidential?',
                                'answer' => 'Yes, we use bank-level encryption and security measures to protect all client data. Our system is designed with attorney-client privilege in mind, ensuring complete confidentiality and compliance with legal industry standards.'
                            ],
                            [
                                'question' => 'Can multiple team members collaborate on cases?',
                                'answer' => 'Absolutely! Our system supports team collaboration with role-based permissions. You can assign team members to cases, share documents securely, and track everyone\'s contributions while maintaining proper access controls.'
                            ],
                            [
                                'question' => 'How does time tracking and billing work?',
                                'answer' => 'Our integrated time tracking allows you to log billable hours directly within cases and tasks. The system automatically generates invoices based on your time entries and billing rates, streamlining your billing process.'
                            ],
                            [
                                'question' => 'Can I manage court schedules and deadlines?',
                                'answer' => 'Yes, our calendar system helps you track court dates, filing deadlines, and important case milestones. You\'ll receive automated reminders to ensure you never miss critical dates.'
                            ],
                            [
                                'question' => 'What types of documents can I store and manage?',
                                'answer' => 'You can store all types of legal documents including contracts, pleadings, evidence, correspondence, and research materials. Our document management system supports version control and secure sharing.'
                            ],
                            [
                                'question' => 'Is there a mobile app available?',
                                'answer' => 'Yes, our responsive web application works seamlessly on mobile devices, allowing you to access case information, update time entries, and communicate with clients from anywhere.'
                            ],
                            [
                                'question' => 'How do I get started?',
                                'answer' => 'Getting started is easy! Simply sign up for an account, set up your firm profile, and begin adding your cases and clients. Our intuitive interface requires no technical expertise to use effectively.'
                            ]
                        ];
                        break;
                    }
                }
                
                $settings->config_sections = $configSections;
                $settings->save();
            }
        }
    }
}
