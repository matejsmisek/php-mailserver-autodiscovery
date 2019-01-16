PHP Mail server autodiscovery
=========

Translates provided email address into full IMAP/POP3/SMTP server configuration to be used by PHP


## Library Features
- First it tries Mozzila ISPDB for any records from email domain
- Compatible with Thunderbird autoconfiguration XML
- Compatible with Outlook autodiscovery XML
- Tries to resolve MX and SRV DNS records for more accurate finds
- Optionally can perform test connection and login to found configurations


## Limitations
- Gmail accounts cannot be validated via tester and will usually report warning email to user


## Why you might need it
If you are building PHP application which integrates user mailbox. Users usually don't know their email server settings and popular e-mail clients (Thunderbird, Outlook) already fills this information for them

## Installation & loading
Library is available on [Packagist](https://packagist.org/packages/balgor/mailserver-autodiscovery), and installation via [Composer](https://getcomposer.org) is the recommended way to install Mail server autodiscovery. Just add this line to your `composer.json` file:

```json
"phpmailer/mailserver-autodiscovery": "~1.1"
```

or run

```sh
composer require balgor/mailserver-autodiscovery
```


## Planned Features
- Validation of CRAM-MD5 password logins
- Autoconfiguration if no service was found by try connecting to Guessed service and reading server capabilities
- Easily fetch fully form string from server to use with php_imap library
- Convert SMTP server configuration for Swift_Mailer library usage