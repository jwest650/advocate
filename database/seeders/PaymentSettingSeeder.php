<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSettingSeeder extends Seeder
{
    public function run(): void
    {
        $paymentData = [
            "currency" => "USD",
            "currency_symbol" => "$",
            "is_manually_enabled" => false,
            "is_bank_enabled" => true,
            "bank_detail" => "Bank: ICICI\nA/C No.: **************",
            "is_stripe_enabled" => true,
            "stripe_key" => "",
            "stripe_secret" => "",
            "is_paypal_enabled" => true,
            "paypal_mode" => "sandbox",
            "paypal_client_id" => "",
            "paypal_secret_key" => "",
            "is_razorpay_enabled" => true,
            "razorpay_key" => "",
            "razorpay_secret" => "",
            "is_mercadopago_enabled" => true,
            "mercadopago_mode" => "sandbox",
            "mercadopago_access_token" => "",
            "is_paystack_enabled" => true,
            "paystack_public_key" => "",
            "paystack_secret_key" => "",
            "is_flutterwave_enabled" => true,
            "flutterwave_public_key" => "",
            "flutterwave_secret_key" => "",
            "is_tap_enabled" => true,
            "tap_secret_key" => "",
            "is_xendit_enabled" => true,
            "xendit_api_key" => "",
            "is_paytr_enabled" => true,
            "paytr_merchant_id" => "",
            "paytr_merchant_key" => "",
            "paytr_merchant_salt" => "",
            "is_mollie_enabled" => true,
            "mollie_api_key" => "",
            "is_toyyibpay_enabled" => true,
            "toyyibpay_category_code" => "",
            "toyyibpay_secret_key" => "",
            "is_paymentwall_enabled" => true,
            "paymentwall_public_key" => "",
            "paymentwall_private_key" => "",
            "is_sspay_enabled" => true,
            "sspay_secret_key" => "",
            "sspay_category_code" => "",
            "is_benefit_enabled" => true,
            "benefit_mode" => "sandbox",
            "benefit_secret_key" => "",
            "benefit_public_key" => "",
            "is_iyzipay_enabled" => true,
            "iyzipay_mode" => "sandbox",
            "iyzipay_secret_key" => "",
            "iyzipay_public_key" => "",
            "is_aamarpay_enabled" => true,
            "aamarpay_store_id" => "",
            "aamarpay_signature" => "",
            "is_midtrans_enabled" => true,
            "midtrans_mode" => "sandbox",
            "midtrans_secret_key" => "",
            "is_yookassa_enabled" => true,
            "yookassa_shop_id" => "",
            "yookassa_secret_key" => "",
            "is_nepalste_enabled" => false,
            "nepalste_mode" => "sandbox",
            "nepalste_secret_key" => "",
            "nepalste_public_key" => "",
            "is_paiement_enabled" => true,
            "paiement_merchant_id" => "",
            "is_cinetpay_enabled" => true,
            "cinetpay_site_id" => "",
            "cinetpay_api_key" => "",
            "cinetpay_secret_key" => "",
            "is_payhere_enabled" => true,
            "payhere_mode" => "sandbox",
            "payhere_merchant_id" => "",
            "payhere_merchant_secret" => "",
            "payhere_app_id" => "",
            "payhere_app_secret" => "",
            "is_fedapay_enabled" => true,
            "fedapay_mode" => "sandbox",
            "fedapay_secret_key" => "",
            "fedapay_public_key" => "",
            "is_authorizenet_enabled" => true,
            "authorizenet_mode" => "sandbox",
            "authorizenet_merchant_id" => "",
            "authorizenet_transaction_key" => "",
            "is_khalti_enabled" => true,
            "khalti_secret_key" => "",
            "khalti_public_key" => "",
            "is_easebuzz_enabled" => true,
            "easebuzz_merchant_key" => "",
            "easebuzz_salt_key" => "",
            "easebuzz_environment" => "demo",
            "is_ozow_enabled" => true,
            "ozow_mode" => "sandbox",
            "ozow_site_key" => "",
            "ozow_private_key" => "",
            "ozow_api_key" => "",
            "is_cashfree_enabled" => true,
            "cashfree_mode" => "sandbox",
            "cashfree_secret_key" => "",
            "cashfree_public_key" => "",
            "is_paytabs_enabled" => true,
            "paytabs_profile_id" => "",
            "paytabs_server_key" => "",
            "paytabs_region" => "ARE",
            "paytabs_mode" => "sandbox",
            "is_skrill_enabled" => true,
            "skrill_merchant_id" => "",
            "skrill_secret_word" => "",
            "is_coingate_enabled" => true,
            "coingate_api_token" => "",
            "coingate_mode" => "sandbox",
            "is_payfast_enabled" => true,
            "payfast_merchant_id" => "",
            "payfast_merchant_key" => "",
            "payfast_passphrase" => "",
            "payfast_mode" => "sandbox"
        ];

        // Create for superadmin (user_id = 1)
        foreach ($paymentData as $key => $value) {
            PaymentSetting::firstOrCreate([
                'key' => $key,
                'user_id' => 1
            ], [
                'value' => $value
            ]);
        }

        // Create for all company users
        $companyUsers = User::where('type', 'company')->get();
        foreach ($companyUsers as $companyUser) {
            foreach ($paymentData as $key => $value) {
                PaymentSetting::firstOrCreate([
                    'key' => $key,
                    'user_id' => $companyUser->id
                ], [
                    'value' => $value
                ]);
            }
        }
    }
}