<?php

if (!function_exists('app_url')) {
    require_once __DIR__ . '/url.php';
}

/**
 * Crispy AI — FAQ knowledge for the customer chatbot (no external API).
 *
 * @return list<array{id: string, question: string, answer: string, keywords: list<string>, aliases: list<string>}>
 */
function kk_crispy_ai_faqs(): array
{
    $phone = '09389762763';
    $email = 'support@crispycrave.com';

    return [
        [
            'id' => 'place_order',
            'question' => 'How do I place an order?',
            'keywords' => ['place', 'order', 'menu', 'cart', 'checkout', 'buy', 'purchase', 'add', 'items'],
            'aliases' => ['how to order', 'start ordering', 'order food', 'add to cart', 'make an order'],
            'answer' => 'You can place an order by browsing each restaurant’s menu on screen — pick dishes, sides, and drinks you want. Choose quantities, add them to your cart, then review your cart before checkout so everything is correct.',
        ],
        [
            'id' => 'smartphone',
            'question' => 'Can I order using my own smartphone?',
            'keywords' => ['phone', 'smartphone', 'mobile', 'tablet', 'device', 'browser', 'web', 'internet', 'iphone', 'android'],
            'aliases' => ['use my phone', 'order on mobile', 'works on phone'],
            'answer' => 'Yes. Crispy Crave is web-based — open your browser on any smartphone, tablet, or computer with internet and visit the shop ordering page. A stable connection helps avoid interruptions while you order.',
        ],
        [
            'id' => 'payment',
            'question' => 'What payment methods can I use?',
            'keywords' => ['pay', 'payment', 'cod', 'cash', 'gcash', 'method', 'paid'],
            'aliases' => ['how to pay', 'payment options', 'cash on delivery', 'pay with gcash'],
            'answer' => 'You can pay with Cash on Delivery (COD) or GCash at checkout. Select your preferred method before you confirm — having it ready helps you finish smoothly.',
        ],
        [
            'id' => 'receipt',
            'question' => 'Will I receive a receipt for my order?',
            'keywords' => ['receipt', 'proof', 'download', 'screenshot', 'invoice', 'transaction', 'confirmation'],
            'aliases' => ['proof of payment', 'order confirmation', 'get a receipt'],
            'answer' => 'Yes. After payment, you can view your order details in My orders as proof of your purchase. We recommend saving or screenshotting your order summary for your records.',
        ],
        [
            'id' => 'ready',
            'question' => 'How will I know when my order is ready?',
            'keywords' => ['ready', 'status', 'update', 'notification', 'preparing', 'completed', 'finished', 'track', 'progress'],
            'aliases' => ['when is my order ready', 'order updates', 'know when ready', 'is my food ready'],
            'answer' => 'Use My orders or Track order for real-time status. When the kitchen finishes, the status updates to preparing, out for delivery, or completed. For delivery, a rider picks up and brings your food. Keep notifications on if your device allows them.',
        ],
        [
            'id' => 'delivery',
            'question' => 'Can I have my order delivered?',
            'keywords' => ['deliver', 'delivery', 'rider', 'address', 'bring', 'fee', 'distance', 'ship'],
            'aliases' => ['home delivery', 'deliver to my house', 'delivery option', 'do you deliver'],
            'answer' => 'Yes. Choose delivery at checkout and enter your address. A rider will be assigned; delivery fees may apply by distance. Use a complete, accurate address to avoid delays.',
        ],
        [
            'id' => 'delivery_status',
            'question' => 'How do I know the status of my delivery order?',
            'keywords' => ['delivery status', 'rider', 'picked', 'way', 'track', 'where', 'location', 'arrive', 'eta'],
            'aliases' => ['where is my order', 'track delivery', 'rider location', 'when will it arrive'],
            'answer' => 'Open Track order from My orders. You can see when a rider is assigned, when they pick up your food, and when they are on the way. Check regularly so you know when to expect your order.',
        ],
        [
            'id' => 'cancel_change',
            'question' => 'Can I change or cancel my order after placing it?',
            'keywords' => ['cancel', 'change', 'modify', 'wrong', 'mistake', 'refund', 'void'],
            'aliases' => ['cancel my order', 'change my order', 'undo order', 'cancel delivery'],
            'answer' => 'You may cancel from My orders or Track order while the kitchen has not started preparing and the rider has not picked up. After that, changes or cancellations may not be possible. Double-check your cart before you confirm.',
        ],
        [
            'id' => 'account',
            'question' => 'Do I need to create an account to place an order?',
            'keywords' => ['account', 'register', 'signup', 'sign up', 'login', 'log in', 'create', 'guest', 'signin'],
            'aliases' => ['need an account', 'create account', 'register account', 'without account'],
            'answer' => 'Yes. You need an account to place an order so we can save your order history and contact you if needed. Use a valid email and phone number when you register.',
        ],
        [
            'id' => 'profile',
            'question' => 'How do I edit my profile?',
            'keywords' => ['profile', 'settings', 'account settings', 'change name', 'update name', 'my details', 'edit', 'phone', 'personal'],
            'aliases' => [
                'edit profile',
                'update profile',
                'change my name',
                'update my details',
                'where is profile',
                'open profile',
                'my profile page',
            ],
            'answer' => "To edit your profile, you must be logged in as a customer.\n\n"
                . "1. Open Profile — tap your name in the top bar, the Profile link in the menu, or Profile in the footer.\n"
                . "2. Account section — update your full name and phone, then tap Save account. (Email is shown but cannot be changed on this page.)\n"
                . "3. Payment methods — scroll down to save GCash, bank, or card details (see “How do I save payment details on my profile?”).\n"
                . "4. Change password — use the Change password section at the bottom when you need a new login password.\n\n"
                . 'Profile page: ' . app_url('profile.php'),
            'link' => app_url('profile.php'),
            'link_label' => 'Go to profile',
        ],
        [
            'id' => 'profile_payments',
            'question' => 'How do I save payment details on my profile?',
            'keywords' => [
                'gcash', 'bank', 'card', 'payment details', 'save payment', 'preferred payment',
                'account number', 'bank transfer', 'credit card', 'debit', 'checkout',
            ],
            'aliases' => [
                'save gcash',
                'add gcash number',
                'bank details',
                'card details',
                'saved payment',
                'payment methods on profile',
            ],
            'answer' => "On your Profile page, open the Payment methods section:\n\n"
                . "• GCash — enter your GCash number and account name, then Save payment details.\n"
                . "• Bank transfer — enter bank name, account name, and account number. Leave the number blank if you only want to keep what is already saved.\n"
                . "• Credit / debit card — enter name on card and card number only when updating; we store the last 4 digits and expiry, never the full number or CVV.\n"
                . "• Preferred checkout payment — choose COD, GCash, bank, or card so checkout can preselect your usual method.\n\n"
                . 'Open profile: ' . app_url('profile.php'),
            'link' => app_url('profile.php'),
            'link_label' => 'Edit payment details',
        ],
        [
            'id' => 'crispy_picks',
            'question' => 'What are Crispy Picks?',
            'keywords' => [
                'recommend', 'recommendation', 'weather', 'rainy', 'mood', 'craving',
                'suggest', 'picks', 'perfect for', 'siomai', 'pares',
            ],
            'aliases' => [
                'what should i order',
                'food for rainy day',
                'what to eat today',
                'menu suggestions',
            ],
            'answer' => "Crispy Picks on the home page suggest dishes based on your local weather only.\n\n"
                . "• Sunny or hot days often highlight crispy favorites, cold drinks, and light snacks.\n"
                . "• Rainy weather suggests warm comfort food like beef pares.\n"
                . "• Each card explains why that dish fits the weather — tap it to jump to that shop's menu.",
        ],
        [
            'id' => 'profile_password',
            'question' => 'How do I change my password?',
            'keywords' => ['password', 'change password', 'new password', 'login password', 'update password', 'security'],
            'aliases' => [
                'reset password on profile',
                'change my password',
                'update my password',
                'forgot password profile',
            ],
            'answer' => "Change your password from your Profile page:\n\n"
                . "1. Go to Profile (tap your name in the header or use the Profile link).\n"
                . "2. Scroll to Change password.\n"
                . "3. Enter your current password, then your new password twice (at least 6 characters).\n"
                . "4. Tap Update password.\n\n"
                . "If you cannot log in, use the login page help or contact support — password changes on Profile require your current password.\n\n"
                . 'Profile: ' . app_url('profile.php'),
            'link' => app_url('profile.php'),
            'link_label' => 'Go to profile',
        ],
        [
            'id' => 'privacy',
            'question' => 'Is my personal information kept private?',
            'keywords' => ['privacy', 'private', 'secure', 'security', 'data', 'information', 'password', 'safe'],
            'aliases' => ['is my data safe', 'protect my information', 'privacy policy'],
            'answer' => 'Yes. Your information is stored securely and used for ordering and delivery only. Use a strong password on your account to help protect your details. See our Privacy policy for more.',
        ],
        [
            'id' => 'problem',
            'question' => 'What should I do if there is a problem with my order?',
            'keywords' => ['problem', 'issue', 'wrong', 'missing', 'help', 'contact', 'complaint', 'support', 'incorrect', 'broken'],
            'aliases' => ['something wrong', 'bad order', 'missing items', 'contact support', 'customer service'],
            'answer' => "If something is wrong with your order (missing items, incorrect food, or delivery concerns), contact us:\n\nPhone: {$phone}\nEmail: {$email}\n\nPlease include your order number when you reach out.",
        ],
        [
            'id' => 'rider_apply',
            'question' => 'How do I apply as a rider?',
            'keywords' => ['rider', 'apply', 'application', 'deliver', 'driver', 'job', 'work', 'hire', 'courier', 'motorcycle', 'bike'],
            'aliases' => [
                'become a rider',
                'apply as rider',
                'rider application',
                'sign up as rider',
                'join as rider',
                'want to be a rider',
                'delivery partner',
                'work as rider',
            ],
            'answer' => 'To apply as a Crispy Crave rider, open our rider application page: '
                . app_url('rider/apply.php')
                . "\n\n1. Enter your name, email, password, phone, and vehicle details.\n"
                . "2. Upload your driver's license and a valid ID.\n"
                . "3. Submit — your account stays pending until an admin approves it.\n\n"
                . 'After approval, sign in at the rider portal to accept deliveries.',
            'link' => app_url('rider/apply.php'),
            'link_label' => 'Apply as a rider',
        ],
    ];
}

function kk_crispy_ai_greeting(): string
{
    return 'Hi! I\'m Crispy AI, your guide for Crispy Crave. Ask about ordering, Crispy Picks, delivery, payments, your profile, or how to apply as a rider. Tap a topic below or type your question naturally.';
}

function kk_crispy_ai_off_topic(): string
{
    return 'I\'m sorry — I can only help with Crispy Crave ordering, delivery, payments, your profile, and your account. Try rephrasing, or tap a suggested topic below.';
}

function kk_crispy_ai_thanks(): string
{
    return 'You\'re welcome! If you need anything else about ordering on Crispy Crave, just ask.';
}

function kk_crispy_ai_clarify(): string
{
    return 'I found a few topics that might match. Which one did you mean?';
}
