<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Plan;
use App\Models\LandingPageSetting;
use App\Models\LandingPageCustomPage;
use App\Models\Business;
use App\Models\contact;

class LandingPageController extends Controller
{
    public function show(Request $request)
    {
        $host = $request->getHost();
        $hostParts = explode('.', $host);



        // Check if landing page is enabled in settings
        if (!isLandingPageEnabled()) {
            return redirect()->route('login');
        }

        $landingSettings = LandingPageSetting::getSettings();

        $plans = Plan::where('is_plan_enable', 'on')->get()->map(function ($plan) {
            $features = [];
            if ($plan->enable_custdomain === 'on') $features[] = 'Custom Domain';
            if ($plan->enable_custsubdomain === 'on') $features[] = 'Subdomain';
            if ($plan->pwa_business === 'on') $features[] = 'PWA';
            if ($plan->enable_chatgpt === 'on') $features[] = 'AI Integration';

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'yearly_price' => $plan->yearly_price,
                'duration' => $plan->duration,
                'description' => $plan->description,
                'features' => $features,
                'stats' => [
                    'users' => $plan->max_users,
                    'cases' => $plan->max_cases,
                    'clients' => $plan->max_clients,
                    'storage' => $plan->storage_limit . ' GB',
                ],
                'trial_days' => $plan->trial_day,
                'is_plan_enable' => $plan->is_plan_enable,
                'is_popular' => false // Will be set based on subscriber count
            ];
        });

        // Mark most subscribed plan as popular
        $planSubscriberCounts = Plan::withCount('users')->get()->pluck('users_count', 'id');
        if ($planSubscriberCounts->isNotEmpty()) {
            $mostSubscribedPlanId = $planSubscriberCounts->keys()->sortByDesc(function($planId) use ($planSubscriberCounts) {
                return $planSubscriberCounts[$planId];
            })->first();

            $plans = $plans->map(function($plan) use ($mostSubscribedPlanId) {
                if ($plan['id'] == $mostSubscribedPlanId && $plan['price'] != '0') {
                    $plan['is_popular'] = true;
                }
                return $plan;
            });
        }

        // Get FAQs from settings configuration
        $sections = $landingSettings->config_sections['sections'] ?? [];
        
        $faqSection = collect($sections)->firstWhere('key', 'faq');
        $faqs = collect($faqSection['faqs'] ?? [])
            ->map(function($faq, $index) {
                return [
                    'id' => $index + 1,
                    'question' => $faq['question'] ?? '',
                    'answer' => $faq['answer'] ?? ''
                ];
            });

        return Inertia::render('landing-page/index', [
            'plans' => $plans,
            'testimonials' => [],
            'faqs' => $faqs,
            'customPages' => LandingPageCustomPage::active()->ordered()->get() ?? [],
            'settings' => $landingSettings,
            'sectionData' => [
                'faq' => [
                    'title' => $faqSection['title'] ?? 'Frequently Asked Questions',
                    'subtitle' => $faqSection['subtitle'] ?? "Got questions? We've got answers.",
                    'cta_text' => $faqSection['cta_text'] ?? 'Still have questions?',
                    'button_text' => $faqSection['button_text'] ?? 'Contact Support',
                    'default_faqs' => $faqs->toArray()
                ]
            ]
        ]);
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $contact = new contact();
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->subject = $request->subject;
        $contact->message = $request->message;
        $contact->save();

        return back()->with('success', __('Thank you for your message. We will get back to you soon!'));
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        // NewsletterSubscription::updateOrCreate(
        //     ['email' => $request->email],
        //     ['subscribed_at' => now(), 'unsubscribed_at' => null]
        // );

        return back()->with('success', __('Thank you for subscribing to our newsletter!'));
    }

    public function settings()
    {
        $landingSettings = LandingPageSetting::getSettings();

        return Inertia::render('landing-page/settings', [
            'settings' => $landingSettings
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:255',
            'contact_address' => 'required|string|max:255',
            'config_sections' => 'required|array'
        ]);
        $landingSettings = LandingPageSetting::getSettings();
        $landingSettings->update($request->all());

        return back()->with('success', __('Landing page settings updated successfully!'));
    }
}
