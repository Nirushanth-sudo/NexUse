<?php
/**
 * NexUse — admin category management.
 *
 * MVC layer: Controller.
 * Module owner: Member 4 (admin area), on Member 1's `categories` table.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;

class AdminCategoryController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin.categories', [
            'pageTitle'  => 'Categories',
            'navActive'  => 'admin',
            'adminNav'   => 'categories',
            'categories' => Category::withCounts(),
            'errors'     => Session::takeErrors(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $this->requirePost('/admin/categories');
        $this->verifyCsrf();

        $name = Request::post('name');
        $slug = Category::slugify($name);

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter a category name.';
        } elseif (mb_strlen($name) > 80) {
            $errors['name'] = 'Keep the name under 80 characters.';
        } elseif ($slug === '') {
            $errors['name'] = 'That name contains no usable characters.';
        } elseif (Category::slugTaken($slug)) {
            $errors['name'] = 'A category with that name already exists.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/admin/categories', $errors);
        }

        Category::create(['name' => $name, 'slug' => $slug]);

        $this->flash('success', 'Category "' . $name . '" added.');
        $this->redirect('/admin/categories');
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        $this->requirePost('/admin/categories');
        $this->verifyCsrf();

        $category = Category::find($id);

        if ($category === null) {
            $this->notFound('That category could not be found.');
        }

        $name = Request::post('name');
        $slug = Category::slugify($name);

        if ($name === '' || $slug === '') {
            $this->flash('error', 'Enter a valid category name.');
            $this->redirect('/admin/categories');
        }

        if (Category::slugTaken($slug, $id)) {
            $this->flash('error', 'Another category already uses that name.');
            $this->redirect('/admin/categories');
        }

        Category::update($id, ['name' => $name, 'slug' => $slug]);

        $this->flash('success', 'Category renamed.');
        $this->redirect('/admin/categories');
    }

    public function destroy(int $id): void
    {
        Auth::requireAdmin();
        $this->requirePost('/admin/categories');
        $this->verifyCsrf();

        $category = Category::find($id);

        if ($category === null) {
            $this->notFound('That category could not be found.');
        }

        // Listings keep working: their category_id is set to NULL by the schema.
        Category::delete($id);

        $this->flash('success', 'Category deleted. Listings that used it are now uncategorised.');
        $this->redirect('/admin/categories');
    }
}
