<img src="https://console.finance/images/email/logo.jpg" alt="Console Finance" height="80" style="width: auto;" />

# Console Finance API Client for PHP
API client that enables developers to access and manage the data of their Console Finance account.


## Requirements
1. [Console Finance](https://console.finance) account.
2. Your account must belong to a **business** or you must be a business.
3. **API credentials.** It can be obtained inside the Apps menu in the business settings dashboard.
4. A server with **PHP >= 5.6** installed. **Composer** also recommended.
5. **SSL Certificate.**

## Installation
```bash
composer require consolefinance/api-client-php
```

## Usage
Load the library:
```php
require 'vendor/autoload.php';

use \Console\Finance\AppClient;
```
Send payment request:
```php
$Client->set_api_key('YOUR API KEY');
$Client->set_token($_POST['token']);
$Client->set_state($_POST['state']);
$Client->send_payment_request();
```
