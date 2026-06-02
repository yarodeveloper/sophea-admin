<?php
/**
 * SOPHEA - WhatsApp Business API Configuration
 * 
 * Configuration for WhatsApp Business API integration
 */

// WhatsApp Business API Configuration
define('WHATSAPP_API_ENABLED', true);

// Phone Number ID (from Meta Business)
define('WHATSAPP_PHONE_NUMBER_ID', '619215614617031');

// WhatsApp Business Account ID
define('WHATSAPP_BUSINESS_ACCOUNT_ID', '130339163500704');

// Access Token (REQUIRED - Get from Meta Business Manager)
define('WHATSAPP_ACCESS_TOKEN', 'EAATmFem3hWYBQPcRk0v1hWQn4CkqDq3GbBmeQ06w1D8DVZAZBAwytHZA2QVbsXSXYLTB4Stgp3whv2wntPvaHSTm6ons8ZCqJdr2kZCsTdMlU0fR97lSqxY6ZCBhsDJSK0aFDtXMnSkZCbqbgMyyK8K1doHFrafZB2i46AcvvrhbjDmGSDbejU2ZAeOO0xutUoEbS5ZAjluMecfm1ZAQiQpylSjF2ZBcOob7kgybsvphIEpz7Ps4jaTKZA3GEbuu7RUpqTWvXZBxDFTtvDfHUPin66CQkvDPLEyGCSVUln1LGHDgZDZD');

// API Version (usually v18.0 or latest)
define('WHATSAPP_API_VERSION', 'v18.0');

// Base URL for WhatsApp Business API
define('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/' . WHATSAPP_API_VERSION);

// Certificate/Token (provided by user)
define('WHATSAPP_CERTIFICATE', 'CmUKIQjDsdumgYXYAxIGZW50OndhIghVTk9tZWRpY1DpkcTJBhpA86TdA443Mt6R19WpfcXbMnzcUFcMbeshmhW+9qBrtNTZlPmyjdKnCfZRIB7OE2hJ6cWN/bNtwsVGQ2MVfCe4BRIvbT5SoNb02izgRYuwmaVrKZRS7OBfzPIFr11FgYsc/MAgA9YJQPh8AaiwJSuk4Dg=');

// Default message template (optional)
define('WHATSAPP_DEFAULT_MESSAGE_TEMPLATE', 'Hola {nombre}, gracias por contactarnos. Te responderemos pronto.');

// Enable message logging
define('WHATSAPP_LOG_MESSAGES', true);

// Webhook Configuration
// This token must match the one you set in Meta Business Manager
define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'SwEwGuW1g3DGRyi7rhVdsd6VrmZGYgI4');

// Webhook URL (update with your actual domain)
define('WHATSAPP_WEBHOOK_URL', 'https://ia.sopheamkt.com/webhook_whatsapp.php');

