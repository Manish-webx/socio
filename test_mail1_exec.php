<?php
$_POST = [
    'name' => 'Antigravity Test Lead',
    'phone' => '+91 9999999999',
    'email' => 'test@example.com',
    'service' => 'Website Development',
    'budget' => '₹50,000 – ₹1,00,000',
    'notes' => 'Testing mail receipt from mail1.php logic',
    'url' => 'https://socioninjas.com/contact.html',
    'ajax' => '1'
];

// Let's run the exact mail1.php logic
ob_start();
include __DIR__ . '/mail1.php';
$output = ob_get_clean();

echo "OUTPUT:\n" . $output . "\n";
