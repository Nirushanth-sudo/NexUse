<?php
/**
 * NexUse — local configuration template.
 *
 * Copy this file to `config.local.php` and fill in the values for your machine.
 * `config.local.php` is git-ignored so credentials never reach the repository.
 */

return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'nexuse',
    'db_user' => 'root',
    'db_pass' => 'your-mysql-password-here',

    // Bank details for the "Donate to NexUse" dialog in the footer. Leave this
    // commented out and the dialog shows plainly fake sample details (all
    // zeros). Donation details are meant to be public,
    // so unlike the database password they are safe to commit.
    // 'donation' => [
    //     'account_name'   => 'NexUse',
    //     'account_number' => '',
    //     'bank'           => '',
    //     'branch'         => '',
    //     'swift'          => '',
    //     'reference'      => 'NEXUSE-DONATE',
    // ],

    // Base URL path the app is served from. '' when served from the site root
    // (php -S localhost:8000), '/nexuse' when dropped into XAMPP's htdocs/nexuse.
    'base_url' => '',

    // Show PHP errors on screen. Turn off for a demo or deployment.
    'debug' => true,
];
