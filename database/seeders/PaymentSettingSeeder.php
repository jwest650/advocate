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
            "is_stripe_enabled" => false,
            "stripe_key" => "",
            "stripe_secret" => "",
            "is_paypal_enabled" => false,
            "paypal_mode" => "sandbox",
            "paypal_client_id" => "",
            "paypal_secret_key" => "",
            "is_razorpay_enabled" => false,
            "razorpay_key" => "",
            "razorpay_secret" => "",
            "is_mercadopago_enabled" => false,
            "mercadopago_mode" => "sandbox",
            "mercadopago_access_token" => "",
            "is_paystack_enabled" => false,
            "paystack_public_key" => "",
            "paystack_secret_key" => "",
            "is_flutterwave_enabled" => false,
            "flutterwave_public_key" => "",
            "flutterwave_secret_key" => "",
            "is_tap_enabled" => false,
            "tap_secret_key" => "",
            "is_xendit_enabled" => false,
            "xendit_api_key" => "",
            "is_paytr_enabled" => false,
            "paytr_merchant_id" => "",
            "paytr_merchant_key" => "",
            "paytr_merchant_salt" => "",
            "is_mollie_enabled" => false,
            "mollie_api_key" => "",
            "is_toyyibpay_enabled" => false,
            "toyyibpay_category_code" => "",
            "toyyibpay_secret_key" => "",
            "is_paymentwall_enabled" => false,
            "paymentwall_public_key" => "",
            "paymentwall_private_key" => "",
            "is_sspay_enabled" => false,
            "sspay_secret_key" => "",
            "sspay_category_code" => "",
            "is_benefit_enabled" => false,
            "benefit_mode" => "sandbox",
            "benefit_secret_key" => "",
            "benefit_public_key" => "",
            "is_iyzipay_enabled" => false,
            "iyzipay_mode" => "sandbox",
            "iyzipay_secret_key" => "",
            "iyzipay_public_key" => "",
            "is_aamarpay_enabled" => false,
            "aamarpay_store_id" => "",
            "aamarpay_signature" => "",
            "is_midtrans_enabled" => false,
            "midtrans_mode" => "sandbox",
            "midtrans_secret_key" => "",
            "is_yookassa_enabled" => false,
            "yookassa_shop_id" => "",
            "yookassa_secret_key" => "",
            "is_nepalste_enabled" => false,
            "nepalste_mode" => "sandbox",
            "nepalste_secret_key" => "",
            "nepalste_public_key" => "",
            "is_paiement_enabled" => false,
            "paiement_merchant_id" => "",
            "is_cinetpay_enabled" => false,
            "cinetpay_site_id" => "",
            "cinetpay_api_key" => "",
            "cinetpay_secret_key" => "",
            "is_payhere_enabled" => false,
            "payhere_mode" => "sandbox",
            "payhere_merchant_id" => "",
            "payhere_merchant_secret" => "",
            "payhere_app_id" => "",
            "payhere_app_secret" => "",
            "is_fedapay_enabled" => false,
            "fedapay_mode" => "sandbox",
            "fedapay_secret_key" => "",
            "fedapay_public_key" => "",
            "is_authorizenet_enabled" => false,
            "authorizenet_mode" => "sandbox",
            "authorizenet_merchant_id" => "",
            "authorizenet_transaction_key" => "",
            "is_khalti_enabled" => false,
            "khalti_secret_key" => "",
            "khalti_public_key" => "",
            "is_easebuzz_enabled" => false,
            "easebuzz_merchant_key" => "",
            "easebuzz_salt_key" => "",
            "easebuzz_environment" => "demo",
            "is_ozow_enabled" => false,
            "ozow_mode" => "sandbox",
            "ozow_site_key" => "",
            "ozow_private_key" => "",
            "ozow_api_key" => "",
            "is_cashfree_enabled" => false,
            "cashfree_mode" => "sandbox",
            "cashfree_secret_key" => "",
            "cashfree_public_key" => "",
            "is_paytabs_enabled" => false,
            "paytabs_profile_id" => "",
            "paytabs_server_key" => "",
            "paytabs_region" => "ARE",
            "paytabs_mode" => "sandbox",
            "is_skrill_enabled" => false,
            "skrill_merchant_id" => "",
            "skrill_secret_word" => "",
            "is_coingate_enabled" => false,
            "coingate_api_token" => "",
            "coingate_mode" => "sandbox",
            "is_payfast_enabled" => false,
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
