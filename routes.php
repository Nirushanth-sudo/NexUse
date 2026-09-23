<?php
/**
 * NexUse — route table.
 *
 * The single place where a URL is mapped to a controller action. Reading this
 * file top to bottom is the fastest way to see everything the system does, and
 * which member owns each module.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Controllers\Admin\AdminCategoryController;
use App\Controllers\Admin\AdminComplaintController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ComplaintController;
use App\Controllers\HomeController;
use App\Controllers\ListingController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\PageController;
use App\Controllers\ProfileController;
use App\Controllers\RequestController;
use App\Controllers\ReviewController;

/* ------------------------------------------------------------- public ---- */
$router->get('/',        [HomeController::class, 'index']);
$router->get('/about',   [HomeController::class, 'about']);
$router->get('/browse',  [ListingController::class, 'browse']);
$router->get('/terms',   [PageController::class, 'terms']);
$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/contact', [PageController::class, 'contact']);

/* ----------------------------------- authentication · Member 4 · criterion 1 */
$router->form('/register', [AuthController::class, 'register']);
$router->form('/login',    [AuthController::class, 'login']);
$router->post('/logout',   [AuthController::class, 'logout']);

/* ------------------------------------------- listings · Member 1 · CRUD ---- */
$router->get('/listings',              [ListingController::class, 'mine']);       // Read  — my listings
$router->form('/listings/create',      [ListingController::class, 'create']);     // Create
$router->get('/listings/{id}',         [ListingController::class, 'show']);       // Read  — detail
$router->form('/listings/{id}/edit',   [ListingController::class, 'edit']);       // Update
$router->form('/listings/{id}/delete', [ListingController::class, 'destroy']);    // Delete

/* --------------------------------- requests & rentals · Member 2 · CRUD ---- */
$router->form('/requests/send/{id}',     [RequestController::class, 'send']);     // Create
$router->get('/requests/sent',           [RequestController::class, 'sent']);     // Read  — sent
$router->get('/requests/received',       [RequestController::class, 'received']); // Read  — received
$router->get('/requests/{id}',           [RequestController::class, 'show']);     // Read  — detail
$router->post('/requests/{id}/respond',  [RequestController::class, 'respond']);  // Update — accept / reject
$router->form('/requests/{id}/return',   [RequestController::class, 'markReturned']); // Update — return
$router->post('/requests/{id}/withdraw', [RequestController::class, 'withdraw']); // Delete — withdraw

/* ------------------------------------------ messaging · Member 2 ---------- */
$router->get('/messages',             [MessageController::class, 'inbox']);
$router->get('/messages/start/{id}',  [MessageController::class, 'start']);
$router->form('/messages/{id}',       [MessageController::class, 'thread']);

/* -------------------------------------------- profile · Member 3 ---------- */
$router->get('/profile',           [ProfileController::class, 'show']);
$router->form('/profile/edit',     [ProfileController::class, 'edit']);
$router->form('/profile/password', [ProfileController::class, 'password']);
$router->get('/users/{id}',        [ProfileController::class, 'publicProfile']);

/* --------------------------------------------- reviews · Member 3 · CRUD -- */
$router->get('/reviews',                [ReviewController::class, 'mine']);       // Read
$router->form('/reviews/write/{id}',    [ReviewController::class, 'write']);      // Create
$router->form('/reviews/{id}/edit',     [ReviewController::class, 'edit']);       // Update
$router->post('/reviews/{id}/delete',   [ReviewController::class, 'destroy']);    // Delete

/* ------------------------------------------ complaints · Member 3 · CRUD -- */
$router->get('/complaints',              [ComplaintController::class, 'mine']);     // Read
$router->form('/complaints/report',      [ComplaintController::class, 'report']);   // Create
$router->form('/complaints/{id}/edit',   [ComplaintController::class, 'edit']);     // Update
$router->post('/complaints/{id}/delete', [ComplaintController::class, 'destroy']);  // Delete

/* --------------------------------------- notifications · Member 4 · CRUD -- */
$router->get('/notifications',                 [NotificationController::class, 'index']);      // Read
$router->post('/notifications/read/{id}',      [NotificationController::class, 'markRead']);   // Update
$router->post('/notifications/unread/{id}',    [NotificationController::class, 'markUnread']); // Update
$router->post('/notifications/read-all',       [NotificationController::class, 'markAllRead']);// Update
$router->post('/notifications/dismiss/{id}',   [NotificationController::class, 'dismiss']);    // Delete
$router->post('/notifications/clear-read',     [NotificationController::class, 'clearRead']);  // Delete

/* ------------------------------------------------- admin · Member 4 ------- */
$router->get('/admin',                    [AdminDashboardController::class, 'index']);

$router->get('/admin/users',              [AdminUserController::class, 'index']);      // Read
$router->form('/admin/users/create',      [AdminUserController::class, 'create']);     // Create
$router->form('/admin/users/{id}/edit',   [AdminUserController::class, 'edit']);       // Update
$router->post('/admin/users/{id}/delete', [AdminUserController::class, 'destroy']);    // Delete

$router->get('/admin/categories',              [AdminCategoryController::class, 'index']);
$router->post('/admin/categories/create',      [AdminCategoryController::class, 'store']);
$router->post('/admin/categories/{id}/update', [AdminCategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'destroy']);

$router->get('/admin/complaints',               [AdminComplaintController::class, 'index']);
$router->form('/admin/complaints/{id}',         [AdminComplaintController::class, 'show']);

$router->form('/admin/broadcast',         [AdminDashboardController::class, 'broadcast']);
