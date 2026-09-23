<?php
/**
 * NexUse — static information pages.
 *
 * MVC layer: Controller. Terms, privacy and contact — the pages a marker looks
 * for in the footer of any real platform.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    /**
     * Terms and conditions.
     */
    public function terms(): void
    {
        $this->view('pages.terms', [
            'pageTitle'   => 'Terms and Conditions',
            'navActive'   => 'terms',
            'lastUpdated' => '4 September 2026',
        ]);
    }

    /**
     * Privacy notice — what the platform stores and why.
     */
    public function privacy(): void
    {
        $this->view('pages.privacy', [
            'pageTitle'   => 'Privacy Notice',
            'navActive'   => 'privacy',
            'lastUpdated' => '4 September 2026',
        ]);
    }

    /**
     * Contact details.
     */
    public function contact(): void
    {
        $this->view('pages.contact', [
            'pageTitle' => 'Contact us',
            'navActive' => 'contact',
        ]);
    }
}
