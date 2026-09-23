<?php
/**
 * NexUse — view helpers.
 *
 * MVC layer: shared presentation helpers. Available to every View, and to
 * Controllers where a label is needed. They format and escape; they never query.
 */

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Session;

/* --------------------------------------------------------------- output -- */

/**
 * Escape a value for safe output in HTML. Used on every echoed variable.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build an application URL, honouring the configured base path.
 */
function url(string $path = ''): string
{
    $base = rtrim((string) Config::get('base_url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? ($base === '' ? '/' : $base) : $base . '/' . $path;
}

/**
 * Build a URL to a file in public/assets.
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Is the current path this one? Used to mark navigation active.
 */
function is_current(string $path): bool
{
    return App\Core\Request::path() === '/' . trim($path, '/');
}

/* ---------------------------------------------------------- formatting -- */

/**
 * Format a rupee amount, or a dash when there is no price.
 */
function money(mixed $amount): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }

    return 'Rs ' . number_format((float) $amount, 2);
}

/**
 * Render a date as a short readable string.
 */
function short_date(?string $date): string
{
    if (empty($date)) {
        return '—';
    }

    return date('j M Y', strtotime($date));
}

/**
 * Render a timestamp as a relative age, e.g. "3 days ago".
 */
function time_ago(?string $datetime): string
{
    if (empty($datetime)) {
        return '';
    }

    $diff = time() - strtotime($datetime);

    if ($diff < 0) {
        return short_date($datetime);
    }
    if ($diff < 60) {
        return 'just now';
    }

    $units = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
    ];

    foreach ($units as $seconds => $label) {
        if ($diff >= $seconds) {
            $count = (int) floor($diff / $seconds);

            return $count . ' ' . $label . ($count > 1 ? 's' : '') . ' ago';
        }
    }

    return 'just now';
}

/**
 * How many days between two dates, inclusive of neither end.
 */
function days_between(?string $from, ?string $to): int
{
    if (empty($from) || empty($to)) {
        return 0;
    }

    return max(0, (int) round((strtotime($to) - strtotime($from)) / 86400));
}

/**
 * Truncate text for card summaries, breaking on a word boundary.
 */
function excerpt(string $text, int $length = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    $cut = mb_substr($text, 0, $length);
    $gap = mb_strrpos($cut, ' ');

    return rtrim($gap !== false ? mb_substr($cut, 0, $gap) : $cut, ',.;:') . '…';
}

/**
 * Initials for an avatar circle.
 */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

    return mb_strtoupper($first . $last);
}

/**
 * Render a star rating as text, e.g. "★★★★☆".
 */
function stars(float|int|null $rating): string
{
    $filled = (int) round((float) $rating);

    return str_repeat('★', max(0, min(5, $filled))) . str_repeat('☆', max(0, 5 - $filled));
}

/* -------------------------------------------------------------- labels -- */

/**
 * Human label for the listing_type enum.
 */
function listing_type_label(string $type): string
{
    return [
        'sell'   => 'For sale',
        'rent'   => 'For rent',
        'share'  => 'To borrow',
        'donate' => 'Donation',
    ][$type] ?? ucfirst($type);
}

/**
 * The request_type a listing_type produces.
 */
function request_type_for(string $listingType): string
{
    return [
        'sell'   => 'buy',
        'rent'   => 'rent',
        'share'  => 'borrow',
        'donate' => 'donation',
    ][$listingType] ?? 'buy';
}

/**
 * Human label for the request_type enum.
 */
function request_type_label(string $type): string
{
    return [
        'buy'      => 'Purchase',
        'rent'     => 'Rental',
        'borrow'   => 'Borrow',
        'donation' => 'Donation',
    ][$type] ?? ucfirst($type);
}

/**
 * Human label for the item_condition enum.
 */
function condition_label(string $condition): string
{
    return [
        'new'      => 'New',
        'like_new' => 'Like new',
        'good'     => 'Good',
        'fair'     => 'Fair',
        'poor'     => 'Poor',
    ][$condition] ?? ucfirst($condition);
}

/**
 * Human label for the return_condition enum.
 */
function return_condition_label(?string $condition): string
{
    if ($condition === null || $condition === '') {
        return '—';
    }

    return [
        'as_given'     => 'Returned as given',
        'minor_damage' => 'Minor damage',
        'major_damage' => 'Major damage',
        'not_returned' => 'Not returned',
    ][$condition] ?? ucfirst($condition);
}

/**
 * Does this listing type involve the item coming back?
 */
function is_returnable(string $listingType): bool
{
    return in_array($listingType, ['rent', 'share'], true);
}

/* ------------------------------------------------------ forms and auth -- */

/**
 * A remembered form value, for redrawing a rejected form.
 */
function old(string $field, mixed $default = ''): mixed
{
    return Session::old($field, $default);
}

/**
 * The CSRF hidden input. Goes inside every POST form.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(Session::csrfToken()) . '">';
}

/**
 * The signed-in user, or null.
 *
 * @return array<string, mixed>|null
 */
function auth_user(): ?array
{
    return Auth::user();
}

function auth_id(): ?int
{
    return Auth::id();
}

function is_logged_in(): bool
{
    return Auth::check();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

/* ------------------------------------------- feature round 3 helpers ----- */

/**
 * "New" or "Used" — the plain, at-a-glance answer a buyer wants first.
 *
 * The item_condition enum has five values; only `new` is genuinely new, so
 * everything else collapses to "Used". condition_label() still gives the full
 * detail (Like new, Good, Fair, Poor) where there is room for it.
 */
function condition_flag(string $condition): string
{
    return $condition === 'new' ? 'New' : 'Used';
}

/**
 * Is this listing type one where "new or used" is worth stating up front?
 *
 * Sale listings above all — someone paying money needs to know before they
 * click. Rentals and loans are always used by definition.
 */
function shows_condition_flag(string $listingType): bool
{
    return $listingType === 'sell';
}

/**
 * Avatar markup for a user: their uploaded picture, or their initials.
 *
 * @param array<string, mixed> $person Needs `name`, and `avatar_path` if it has one.
 * @param string               $size   '', 'avatar-sm' or 'avatar-lg'
 */
function avatar(array $person, string $size = ''): string
{
    $classes = trim('avatar ' . $size);
    $name    = (string) ($person['name'] ?? '');
    $path    = $person['avatar_path'] ?? null;

    if (is_string($path) && $path !== '') {
        return '<span class="' . e($classes) . ' avatar-photo">'
             . '<img src="' . e(url($path)) . '" alt="' . e($name) . '" loading="lazy">'
             . '</span>';
    }

    return '<span class="' . e($classes) . '">' . e(initials($name)) . '</span>';
}

/**
 * Where an item is, shown only to signed-in members.
 *
 * Location is the one field that turns a listing into a doorstep, so it is kept
 * behind the sign-in wall. Guests see an invitation instead of a place name.
 */
function location_or_prompt(?string $location, string $fallback = 'Sri Lanka'): string
{
    if (!is_logged_in()) {
        return '<a class="locked-hint" href="' . e(url('/login'))
             . '" title="Locations are shown to signed-in members">🔒 Sign in to see</a>';
    }

    return e(($location ?? '') !== '' ? (string) $location : $fallback);
}

/* ------------------------------------------- feature round 4 helpers ----- */

/**
 * Bank details for the "Donate to NexUse" dialog.
 *
 * Read from the `donation` block in config/config.local.php. Until a real
 * account number is set, obviously fake sample values are returned with
 * `is_placeholder` true. The values are kept plainly fake (all zeros, XXXX)
 * because a realistic-looking invented account number is how a donation goes
 * nowhere.
 *
 * @return array{account_name: string, account_number: string, bank: string,
 *               branch: string, swift: string, reference: string, is_placeholder: bool}
 */
function donation_details(): array
{
    $sample = [
        'account_name'   => 'NexUse',
        'account_number' => '0000 0000 0000',
        'bank'           => 'Bank name',
        'branch'         => 'Branch name',
        'swift'          => 'XXXXLKLX',
        'reference'      => 'NEXUSE-DONATE',
    ];

    $configured = \App\Core\Config::get('donation');

    if (!is_array($configured) || trim((string) ($configured['account_number'] ?? '')) === '') {
        return $sample + ['is_placeholder' => true];
    }

    $details = [];
    foreach ($sample as $key => $fallback) {
        $value         = trim((string) ($configured[$key] ?? ''));
        $details[$key] = $value !== '' ? $value : ($key === 'reference' ? $fallback : '—');
    }

    return $details + ['is_placeholder' => false];
}
