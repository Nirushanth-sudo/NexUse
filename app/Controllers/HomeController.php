<?php
/**
 * NexUse — home and information pages.
 *
 * MVC layer: Controller.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ItemRequest;
use App\Models\Listing;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * The public landing page.
     */
    public function index(): void
    {
        $sections = [];

        foreach (['sell', 'rent', 'share', 'donate'] as $type) {
            $items = Listing::recentByType($type);

            if (!empty($items)) {
                $sections[$type] = $items;
            }
        }

        $this->view('home.index', [
            'pageTitle' => 'Home',
            'navActive' => 'home',
            'sections'  => $sections,
            'stats'     => [
                'listings'  => Listing::count("status = 'available'"),
                'members'   => User::count("role = 'member' AND status = 'active'"),
                'exchanges' => ItemRequest::completedCount(),
                'donations' => Listing::count("listing_type = 'donate'"),
            ],
        ]);
    }

    /**
     * How the four exchange types work.
     */
    public function about(): void
    {
        $this->view('home.about', [
            'pageTitle' => 'How it works',
            'navActive' => 'about',
        ]);
    }
}
