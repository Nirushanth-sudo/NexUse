<?php
/**
 * NexUse — admin side navigation.
 *
 * MVC layer: View partial. Member 4.
 *
 * @var string $adminNav Key of the current admin page.
 */

$items = [
    'dashboard'  => ['Dashboard',  '/admin'],
    'users'      => ['Users',      '/admin/users'],
    'categories' => ['Categories', '/admin/categories'],
    'complaints' => ['Complaints', '/admin/complaints'],
    'broadcast'  => ['Broadcast',  '/admin/broadcast'],
];
?>
<nav class="side-nav">
  <div class="side-head">Administration</div>
  <?php foreach ($items as $key => [$label, $path]): ?>
    <a href="<?= url($path) ?>" class="<?= ($adminNav ?? '') === $key ? 'active' : '' ?>">
      <?= e($label) ?>
    </a>
  <?php endforeach; ?>
  <div class="side-head" style="margin-top:8px;">Public site</div>
  <a href="<?= url('/browse') ?>">Browse listings</a>
  <a href="<?= url('/') ?>">Home page</a>
</nav>
