# Admin Route Template

When creating new admin controllers, add these routes to `/var/www/html/GSCMS/routes/web.php` inside the admin route group.

## Route Template

```php
// In routes/web.php, inside the admin group:
$router->group(['middleware' => 'role:super_admin,competition_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {

    // Your Feature Management - Add these lines
    $router->get('/yourfeature', 'YourFeatureController@index', 'admin.yourfeature');
    $router->get('/yourfeature/create', 'YourFeatureController@create', 'admin.yourfeature.create');
    $router->post('/yourfeature', 'YourFeatureController@store', 'admin.yourfeature.store');
    $router->get('/yourfeature/{id}', 'YourFeatureController@show', 'admin.yourfeature.show');
    $router->get('/yourfeature/{id}/edit', 'YourFeatureController@edit', 'admin.yourfeature.edit');
    $router->put('/yourfeature/{id}', 'YourFeatureController@update', 'admin.yourfeature.update');
    $router->delete('/yourfeature/{id}', 'YourFeatureController@destroy', 'admin.yourfeature.destroy');

    // Existing routes...
});
```

## URL Structure

With the above routes, your URLs will be:
- **Index**: `/admin/yourfeature`
- **Create**: `/admin/yourfeature/create`
- **Store**: `POST /admin/yourfeature`
- **Show**: `/admin/yourfeature/123`
- **Edit**: `/admin/yourfeature/123/edit`
- **Update**: `PUT /admin/yourfeature/123`
- **Delete**: `DELETE /admin/yourfeature/123`

## Route Naming Convention

- **Index**: `admin.yourfeature`
- **Create**: `admin.yourfeature.create`
- **Store**: `admin.yourfeature.store`
- **Show**: `admin.yourfeature.show`
- **Edit**: `admin.yourfeature.edit`
- **Update**: `admin.yourfeature.update`
- **Delete**: `admin.yourfeature.destroy`

## Adding to Sidebar

Add your feature to the admin sidebar in `/var/www/html/GSCMS/app/Views/partials/_admin_sidebar.php`:

```php
<li class="nav-item">
    <a href="<?= url('/admin/yourfeature') ?>" class="nav-link <?= isActiveAdminNav('/admin/yourfeature', $currentPath) ?>">
        <i class="nav-icon fas fa-your-icon"></i>
        <span class="nav-text">Your Feature</span>
    </a>
</li>
```

## Complete Checklist

- [ ] Routes added to `routes/web.php` in admin group
- [ ] Controller created in `app/Controllers/Admin/YourFeatureController.php`
- [ ] Views created in `app/Views/admin/yourfeature/` directory
- [ ] Sidebar link added (optional)
- [ ] Database tables exist (if needed)
- [ ] Model created (if needed)
- [ ] Tested all CRUD operations