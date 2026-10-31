<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Enums\RobotsDirective;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class LaunchContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPages();
        $this->seedFaqs();

        Cache::forget('sitemap.xml');
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'About SOGRATE',
                'excerpt' => 'Texas-made beef jerky with roots in backyard BBQ, competition, and shared flavor.',
                'meta_title' => 'About SOGRATE | Texas-Made Beef Jerky',
                'meta_description' => 'Learn how SOGRATE grew from backyard BBQ and a simple phrase into a Texas-made beef jerky brand.',
                'status' => PageStatus::Published->value,
                'published_at' => now(),
                'body' => <<<'HTML'
<p><strong>SOGRATE</strong> began with backyard BBQ and a simple phrase: &ldquo;Man, this is so great.&rdquo; That phrase became our name and carried us into our first BBQ competition.</p>
<p>We later expanded into vending and catering. Beef jerky started as a way to promote the brand and share our flavor, eventually becoming part of a retail operation that also sold BBQ and homebrew supplies.</p>
<p>That retail chapter has closed. Today our focus is beef jerky: Texas made, small-batch, and seriously good.</p>
<p><strong>Texas Made. Seriously Good Jerky.</strong></p>
HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'excerpt' => 'How SOGRATE handles customer information in its current website and order workflow.',
                'meta_title' => 'Privacy Policy | SOGRATE',
                'meta_description' => 'Draft privacy information for SOGRATE customers, pending owner and legal review.',
                'status' => PageStatus::Draft->value,
                'published_at' => null,
                'body' => $this->draftNotice('Privacy Policy').<<<'HTML'
<p>SOGRATE collects the information needed to provide customer accounts, shopping carts, checkout, order fulfillment, customer support, and transactional communications.</p>
<h2>Information we collect</h2>
<ul>
<li>Account details such as name, email address, and password credentials.</li>
<li>Contact and address information supplied during account setup, checkout, or pickup selection.</li>
<li>Order, fulfillment, payment-status, and customer-support records.</li>
<li>Technical information needed for sessions, cart persistence, security, and abuse prevention.</li>
</ul>
<p>Payment card details are handled by Stripe. SOGRATE does not store raw card numbers, card security codes, or full card credentials on its own servers.</p>
<h2>How information is used</h2>
<ul>
<li>Process and fulfill orders.</li>
<li>Provide customer accounts, order history, saved addresses, and optional saved payment methods.</li>
<li>Send transactional messages such as verification, password reset, order, fulfillment, and cancellation notices.</li>
<li>Prevent fraud, abuse, and unauthorized access.</li>
<li>Maintain business and accounting records.</li>
</ul>
<h2>Cookies and sessions</h2>
<p>SOGRATE uses essential session and security cookies to maintain login state, shopping carts, checkout context, and CSRF protection.</p>
<h2>Service providers and retention</h2>
<p>[OWNER/LEGAL REVIEW REQUIRED: Confirm the current hosting, email, payment, backup, and analytics providers; document their data-processing terms and retention periods.]</p>
<h2>Customer requests</h2>
<p>[OWNER/LEGAL REVIEW REQUIRED: Define the process, response period, and contact channel for access, correction, deletion, or other privacy requests.]</p>
<h2>Contact</h2>
<p>Privacy questions can be submitted through the <a href="/contact">Contact page</a>. [OWNER/LEGAL REVIEW REQUIRED: Add the approved business identity and privacy contact details.]</p>
HTML,
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'excerpt' => 'Draft terms for using the SOGRATE website and placing orders.',
                'meta_title' => 'Terms & Conditions | SOGRATE',
                'meta_description' => 'Draft website and ordering terms for SOGRATE customers, pending owner and legal review.',
                'status' => PageStatus::Draft->value,
                'published_at' => null,
                'body' => $this->draftNotice('Terms & Conditions').<<<'HTML'
<p>These terms describe use of the SOGRATE website and ordering process. They are draft terms and require owner and legal approval before publication.</p>
<h2>Orders and pricing</h2>
<p>Product availability, prices, fulfillment options, fees, and taxes are displayed during the ordering process. An order is subject to validation, payment verification, and fulfillment availability.</p>
<h2>Payments</h2>
<p>Payments are processed by Stripe. SOGRATE does not store raw card details. A submitted payment is not treated as complete until the payment is verified through the application's payment reconciliation process.</p>
<h2>Accounts</h2>
<p>Customers are responsible for keeping account credentials secure and for using accurate contact and delivery information. Guest checkout is available for customers who do not create an account.</p>
<h2>Fulfillment</h2>
<p>Available shipping, local delivery, and pickup options are shown during checkout. Customers must provide accurate fulfillment information and follow any pickup or delivery instructions provided for the selected method.</p>
<h2>Acceptable use</h2>
<p>Customers may not misuse the website, attempt unauthorized access, interfere with checkout or payment processing, or submit false order or contact information.</p>
<h2>Content and intellectual property</h2>
<p>SOGRATE branding, product content, photography, and website content may not be copied or used commercially without permission.</p>
<h2>Disclaimers and liability</h2>
<p>[OWNER/LEGAL REVIEW REQUIRED: Add approved disclaimers, limitation-of-liability language, and any legally required consumer notices applicable to the business.]</p>
<h2>Governing law and business identity</h2>
<p>[OWNER/LEGAL REVIEW REQUIRED: Add the approved legal business name, business contact details, governing-law jurisdiction, and dispute-resolution terms.]</p>
<h2>Contact</h2>
<p>Questions about these terms can be submitted through the <a href="/contact">Contact page</a>.</p>
HTML,
            ],
            [
                'slug' => 'shipping-policy',
                'title' => 'Shipping & Delivery Policy',
                'excerpt' => 'Draft information about SOGRATE shipping, local delivery, and pickup.',
                'meta_title' => 'Shipping & Delivery Policy | SOGRATE',
                'meta_description' => 'Draft shipping, local delivery, and pickup policy information for SOGRATE customers.',
                'status' => PageStatus::Draft->value,
                'published_at' => null,
                'body' => $this->draftNotice('Shipping & Delivery Policy').<<<'HTML'
<p>Available shipping, local delivery, and pickup methods are shown during checkout when enabled by SOGRATE.</p>
<h2>Shipping</h2>
<p>Eligible destinations, shipping rates, and available methods are displayed during checkout. Delivery estimates, carrier availability, and tracking are not guaranteed unless explicitly shown for the selected shipment. Tracking information is added when available.</p>
<h2>Local delivery</h2>
<p>Local delivery is available only in supported ZIP codes shown during checkout. Delivery fees and any available instructions are displayed before payment.</p>
<h2>Pickup</h2>
<p>Pickup locations, contact details, instructions, and any applicable fees are shown during checkout. Customers should follow the pickup instructions and bring their order reference.</p>
<h2>Food products and destination restrictions</h2>
<p>[OWNER/REGULATORY REVIEW REQUIRED: Confirm permitted shipping destinations, food-product handling, packaging, delivery expectations, and any interstate or local restrictions.]</p>
<h2>Damaged or incorrect orders</h2>
<p>Contact SOGRATE through the <a href="/contact">Contact page</a> with the order reference and details. [OWNER REVIEW REQUIRED: Define the approved resolution process before publication.]</p>
HTML,
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund & Cancellation Policy',
                'excerpt' => 'Draft information about SOGRATE cancellations, refunds, and food-product orders.',
                'meta_title' => 'Refund & Cancellation Policy | SOGRATE',
                'meta_description' => 'Draft refund and cancellation information for SOGRATE customers, pending business approval.',
                'status' => PageStatus::Draft->value,
                'published_at' => null,
                'body' => $this->draftNotice('Refund & Cancellation Policy').<<<'HTML'
<p>Contact SOGRATE before returning any product or requesting a refund. Do not send food products back without written return instructions and an approved return address.</p>
<h2>Cancellations</h2>
<p>Cancellation requests are reviewed against the order and fulfillment state. Once an order has entered fulfillment, cancellation may no longer be possible. Paid orders require additional review before cancellation.</p>
<h2>Refunds</h2>
<p>Approved refunds are processed through the original Stripe payment method. Refund availability and timing depend on the payment and financial institution. Submitting a request does not create an approved refund.</p>
<h2>Food-product eligibility</h2>
<p>[OWNER/LEGAL REVIEW REQUIRED: Define approved refund eligibility, time limits, food-safety restrictions, and handling for damaged or incorrect orders before publication.]</p>
<h2>Contact</h2>
<p>Use the <a href="/contact">Contact page</a> with the order reference and reason for the request.</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            if (Page::withTrashed()->where('slug', $page['slug'])->exists()) {
                continue;
            }

            Page::create(
                $page + [
                    'template' => PageTemplate::Default->value,
                    'robots' => RobotsDirective::IndexFollow->value,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'question' => 'How do I place an order?',
                'answer' => '<p>Browse the <a href="/shop">Shop</a> or use <a href="/quick-order">Quick Order</a>, add items to your cart, then continue to checkout. Review your fulfillment details and complete payment through the secure Stripe payment section.</p>',
                'category' => 'Ordering',
                'active' => true,
                'sort_order' => 1,
            ],
            [
                'question' => 'What fulfillment methods are available?',
                'answer' => '<p>SOGRATE supports shipping, local delivery, and pickup when those methods are enabled and available for your order. The checkout page shows the methods, fees, and destinations currently available to you.</p>',
                'category' => 'Ordering',
                'active' => true,
                'sort_order' => 2,
            ],
            [
                'question' => 'Do I need an account to place an order?',
                'answer' => '<p>No. Guest checkout is available. An account is optional and provides saved addresses, order history, and optional saved-card management for registered customers.</p>',
                'category' => 'Accounts',
                'active' => true,
                'sort_order' => 3,
            ],
            [
                'question' => 'How does order tracking work?',
                'answer' => '<p>Registered customers can review order details in their account. Guest access uses the secure order confirmation link. Shipping tracking information is displayed when a carrier and tracking number are recorded; local delivery and pickup orders use the fulfillment status shown on the order.</p>',
                'category' => 'Fulfillment',
                'active' => true,
                'sort_order' => 4,
            ],
            [
                'question' => 'How can I contact SOGRATE?',
                'answer' => '<p>Use the <a href="/contact">Contact page</a> to send a message to the SOGRATE team.</p>',
                'category' => 'Support',
                'active' => true,
                'sort_order' => 5,
            ],
            [
                'question' => 'How are refunds and cancellations handled?',
                'answer' => '<p>Refund and cancellation terms require final business approval. Please contact SOGRATE with your order reference before returning a product or requesting a cancellation.</p>',
                'category' => 'Policies',
                'active' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                $faq + [
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }

    private function draftNotice(string $title): string
    {
        return '<p><strong>DRAFT &mdash; OWNER/LEGAL REVIEW REQUIRED.</strong> This '.$title.' is not approved for publication. Business-specific terms must be completed and approved before this page is published.</p>';
    }
}
