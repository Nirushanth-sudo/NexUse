<?php
/**
 * NexUse — listings.
 *
 * MVC layer: Controller.
 * Module owner: Member 1 · interim criterion 3.
 *
 * CRUD on the `listings` entity:
 *   Create  → create()
 *   Read    → browse(), show(), mine()
 *   Update  → edit()
 *   Delete  → destroy()
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Category;
use App\Models\ItemRequest;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Throwable;

class ListingController extends Controller
{
    private const PER_PAGE = 12;

    /* ------------------------------------------------------------ READ -- */

    /**
     * Search and filter every available listing.
     */
    public function browse(): void
    {
        $filters = [
            'keyword'   => Request::queryString('q'),
            'type'      => Request::queryString('type'),
            'category'  => Request::queryInt('category'),
            'condition' => Request::queryString('condition'),
            'newness'   => Request::queryString('newness'),
            'min_price' => Request::queryString('min_price'),
            'max_price' => Request::queryString('max_price'),
            // Location is a members-only field. Guests cannot filter on it
            // either — a filter would leak exactly what the display withholds.
            'location'  => Auth::check() ? Request::queryString('location') : '',
        ];

        $sort = Request::queryString('sort', $filters['keyword'] !== '' ? 'relevance' : 'newest');
        $total = Listing::searchCount($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, Request::queryInt('page') ?? 1), $pages);

        $this->view('listings.browse', [
            'pageTitle'  => 'Browse items',
            'navActive'  => 'browse',
            'listings'   => Listing::search($filters, $sort, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'categories' => Category::ordered(),
            'filters'    => $filters,
            'sort'       => $sort,
            'total'      => $total,
            'page'       => $page,
            'pages'      => $pages,
            'hasFilters' => count(array_filter($filters, static fn($v) => $v !== '' && $v !== null)) > 0,
        ]);
    }

    /**
     * One listing in full.
     */
    public function show(int $id): void
    {
        $listing = Listing::findWithOwner($id);

        if ($listing === null || $listing['status'] === 'removed') {
            $this->notFound('That listing is no longer available.');
        }

        $ownerId  = (int) $listing['user_id'];
        $viewerId = Auth::id();

        $this->view('listings.show', [
            'pageTitle'       => (string) $listing['title'],
            'navActive'       => 'browse',
            'listing'         => $listing,
            'images'          => ListingImage::forListing($id),
            'ownerRating'     => User::rating($ownerId),
            'isOwner'         => $viewerId !== null && $viewerId === $ownerId,
            'existingRequest' => ($viewerId !== null && $viewerId !== $ownerId)
                ? ItemRequest::liveFor($id, $viewerId)
                : null,
            'otherListings'   => Listing::othersFromOwner($ownerId, $id),
            'relatedListings' => Listing::related($id),
        ]);
    }

    /**
     * The signed-in user's own listings.
     */
    public function mine(): void
    {
        $user   = Auth::requireLogin();
        $status = Request::queryString('status');

        $listings = Listing::forUser((int) $user['user_id'], $status);

        $counts = ['all' => Listing::count('user_id = ?', [$user['user_id']])];
        foreach (['available', 'reserved', 'completed', 'removed'] as $key) {
            $counts[$key] = Listing::count('user_id = ? AND status = ?', [$user['user_id'], $key]);
        }

        $this->view('listings.mine', [
            'pageTitle' => 'My listings',
            'navActive' => 'listings',
            'listings'  => $listings,
            'status'    => $status,
            'counts'    => $counts,
        ]);
    }

    /* ---------------------------------------------------------- CREATE -- */

    /**
     * Post a new item.
     */
    public function create(): void
    {
        $user = Auth::requireLogin();

        if (!Request::isPost()) {
            $this->view('listings.create', [
                'pageTitle'  => 'Post an item',
                'navActive'  => 'listings',
                'narrow'     => true,
                'categories' => Category::ordered(),
                'errors'     => Session::takeErrors(),
                'user'       => $user,
            ]);

            return;
        }

        $this->verifyCsrf();

        $input  = $this->readListingInput();
        $errors = $this->validateListing($input);

        if (!empty($errors)) {
            $this->redirectWithErrors('/listings/create', $errors);
        }

        try {
            Database::begin();

            $listingId = Listing::create([
                'user_id'        => $user['user_id'],
                'category_id'    => $input['category_id'],
                'title'          => $input['title'],
                'description'    => $input['description'],
                'item_condition' => $input['item_condition'],
                'listing_type'   => $input['listing_type'],
                'price'          => $input['price'],
                'location'       => $input['location'] !== '' ? $input['location'] : ($user['city'] ?? null),
                'status'         => 'available',
            ]);

            $warnings = $this->storeImages($listingId, 0);

            Database::commit();

            foreach ($warnings as $warning) {
                $this->flash('error', 'Image not added — ' . $warning);
            }

            $this->flash('success', 'Your item is now listed.');
            $this->redirect('/listings/' . $listingId);
        } catch (Throwable $exception) {
            Database::rollBack();

            $this->redirectWithErrors('/listings/create', [
                'form' => 'The listing could not be saved. Please try again.'
                    . (\App\Core\Config::get('debug') ? ' (' . $exception->getMessage() . ')' : ''),
            ]);
        }
    }

    /* ---------------------------------------------------------- UPDATE -- */

    /**
     * Edit an existing listing, its images, and its availability.
     */
    public function edit(int $id): void
    {
        $user    = Auth::requireLogin();
        $listing = Listing::find($id);

        if ($listing === null) {
            $this->notFound('That listing could not be found.');
        }

        // Owners edit their own; an admin may edit any.
        if ((int) $listing['user_id'] !== (int) $user['user_id'] && $user['role'] !== 'admin') {
            $this->forbidden('You can only edit your own listings.');
        }

        if (!Request::isPost()) {
            $this->view('listings.edit', [
                'pageTitle'  => 'Edit listing',
                'navActive'  => 'listings',
                'narrow'     => true,
                'listing'    => $listing,
                'images'     => ListingImage::forListing($id),
                'categories' => Category::ordered(),
                'errors'     => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $action = Request::post('action', 'save');

        if ($action === 'remove_image') {
            $this->removeImage($id);
        }

        if ($action === 'make_primary') {
            $imageId = Request::postInt('image_id', 0) ?? 0;

            if (ListingImage::findForListing($imageId, $id) !== null) {
                ListingImage::makePrimary($imageId, $id);
                $this->flash('success', 'Main photo updated.');
            }

            $this->redirect('/listings/' . $id . '/edit');
        }

        $input  = $this->readListingInput();
        $status = Request::post('status', 'available');

        $errors = $this->validateListing($input);

        if (!in_array($status, ['available', 'reserved', 'completed'], true)) {
            $errors['status'] = 'Choose a valid availability.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/listings/' . $id . '/edit', $errors);
        }

        Listing::update($id, [
            'category_id'    => $input['category_id'],
            'title'          => $input['title'],
            'description'    => $input['description'],
            'item_condition' => $input['item_condition'],
            'listing_type'   => $input['listing_type'],
            'price'          => $input['price'],
            'location'       => $input['location'] !== '' ? $input['location'] : null,
            'status'         => $status,
        ]);

        foreach ($this->storeImages($id, ListingImage::countForListing($id)) as $warning) {
            $this->flash('error', 'Image not added — ' . $warning);
        }

        $this->flash('success', 'Listing updated.');
        $this->redirect('/listings/' . $id);
    }

    /* ---------------------------------------------------------- DELETE -- */

    /**
     * Delete a listing, its images and the files behind them.
     *
     * GET shows a confirmation page; POST performs the deletion.
     */
    public function destroy(int $id): void
    {
        $user    = Auth::requireLogin();
        $listing = Listing::find($id);

        if ($listing === null) {
            $this->notFound('That listing could not be found.');
        }

        if ((int) $listing['user_id'] !== (int) $user['user_id'] && $user['role'] !== 'admin') {
            $this->forbidden('You can only delete your own listings.');
        }

        $liveRequests = ItemRequest::count(
            "listing_id = ? AND status IN ('pending', 'accepted')",
            [$id]
        );

        if (!Request::isPost()) {
            $this->view('listings.delete', [
                'pageTitle'    => 'Delete listing',
                'navActive'    => 'listings',
                'formWidth'    => true,
                'listing'      => $listing,
                'liveRequests' => $liveRequests,
            ]);

            return;
        }

        $this->verifyCsrf();

        // Remove the files first; the rows go with the listing by cascade.
        Upload::deleteMany(ListingImage::pathsForListing($id));

        Listing::delete($id);

        $this->flash('success', 'Listing deleted.');
        $this->redirect('/listings');
    }

    /* ---------------------------------------------------------- helpers -- */

    /**
     * Read the listing fields out of the POST body.
     *
     * @return array<string, mixed>
     */
    private function readListingInput(): array
    {
        $categoryId = Request::post('category_id');

        return [
            'title'          => Request::post('title'),
            'description'    => Request::post('description'),
            'listing_type'   => Request::post('listing_type'),
            'item_condition' => Request::post('item_condition', 'good'),
            'category_id'    => $categoryId !== '' ? (int) $categoryId : null,
            'location'       => Request::post('location'),
            'price_raw'      => Request::post('price'),
            'price'          => null,
        ];
    }

    /**
     * Validate listing input, filling in the parsed price.
     *
     * @param  array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateListing(array &$input): array
    {
        $errors = [];

        if ($input['title'] === '') {
            $errors['title'] = 'Give the item a title.';
        } elseif (mb_strlen((string) $input['title']) > 150) {
            $errors['title'] = 'Keep the title under 150 characters.';
        }

        if ($input['description'] === '') {
            $errors['description'] = 'Describe the item so people know what they are asking for.';
        } elseif (mb_strlen((string) $input['description']) < 20) {
            $errors['description'] = 'Add a little more detail — at least 20 characters.';
        }

        if (!in_array($input['listing_type'], Listing::TYPES, true)) {
            $errors['listing_type'] = 'Choose how you want to offer this item.';
        }

        if (!in_array($input['item_condition'], Listing::CONDITIONS, true)) {
            $errors['item_condition'] = "Choose the item's condition.";
        }

        if ($input['category_id'] !== null && Category::find((int) $input['category_id']) === null) {
            $errors['category_id'] = 'Choose a category from the list.';
        }

        // Price is required for selling and renting, ignored for the free types.
        if (in_array($input['listing_type'], ['sell', 'rent'], true)) {
            $raw = (string) $input['price_raw'];

            if ($raw === '') {
                $errors['price'] = $input['listing_type'] === 'rent'
                    ? 'Enter the rent per day.'
                    : 'Enter the asking price.';
            } elseif (!is_numeric($raw) || (float) $raw < 0) {
                $errors['price'] = 'Enter a valid amount.';
            } elseif ((float) $raw > 99999999) {
                $errors['price'] = 'That price is too large.';
            } else {
                $input['price'] = round((float) $raw, 2);
            }
        }

        return $errors;
    }

    /**
     * Store uploaded images against a listing, up to the per-listing cap.
     *
     * @return list<string> Warnings for files that could not be stored.
     */
    private function storeImages(int $listingId, int $existingCount): array
    {
        $warnings = [];
        $files    = Request::files('images');

        if (empty($files)) {
            return $warnings;
        }

        $room  = max(0, ListingImage::MAX_PER_LISTING - $existingCount);
        $files = array_slice($files, 0, $room);
        $index = 0;

        foreach ($files as $file) {
            $result = Upload::image($file);

            if ($result['ok']) {
                ListingImage::attach(
                    $listingId,
                    (string) $result['path'],
                    $existingCount === 0 && $index === 0
                );
                $index++;
            } else {
                $warnings[] = $file['name'] . ': ' . $result['error'];
            }
        }

        return $warnings;
    }

    /**
     * Remove one image and promote a replacement if it was the main one.
     */
    private function removeImage(int $listingId): never
    {
        $imageId = Request::postInt('image_id', 0) ?? 0;
        $image   = ListingImage::findForListing($imageId, $listingId);

        if ($image !== null) {
            ListingImage::delete($imageId);
            Upload::delete((string) $image['image_path']);

            if ((int) $image['is_primary'] === 1) {
                ListingImage::promoteFirst($listingId);
            }

            $this->flash('success', 'Image removed.');
        }

        $this->redirect('/listings/' . $listingId . '/edit');
    }
}
