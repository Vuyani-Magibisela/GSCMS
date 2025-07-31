# Apache Setup for GSCMS

## Option 1: Virtual Host Setup (Recommended)

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName gscms.local
    DocumentRoot /var/www/html/GSCMS/public
    
    <Directory /var/www/html/GSCMS/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Optional: Redirect to HTTPS
    # Redirect permanent / https://gscms.local/
</VirtualHost>
```

Then add to `/etc/hosts`:
```
127.0.0.1    gscms.local
```

Access via: `http://gscms.local`

## Option 2: Subfolder Setup (Current Issue)

If you must use `http://localhost/GSCMS/public/`, follow these steps:

### 1. Update Apache Configuration

Make sure your Apache has `mod_rewrite` enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 2. Directory Structure
```
/var/www/html/
├── GSCMS/
│   ├── .htaccess              # Root redirects
│   ├── public/
│   │   ├── .htaccess          # Application routing
│   │   ├── index.php
│   │   └── ...
│   ├── app/
│   ├── config/
│   └── ...
```

### 3. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/GSCMS
sudo chmod -R 755 /var/www/html/GSCMS
sudo chmod -R 777 /var/www/html/GSCMS/storage
sudo chmod -R 777 /var/www/html/GSCMS/public/uploads
```

### 4. Test URLs
- Main site: `http://localhost/GSCMS/public/`
- Admin login: `http://localhost/GSCMS/public/auth/login`
- Admin dashboard: `http://localhost/GSCMS/public/admin/dashboard`

## Option 3: Document Root Setup (Best for Production)

Set Apache document root directly to the public folder:

1. Edit Apache site configuration:
```apache
DocumentRoot /var/www/html/GSCMS/public
```

2. Access directly via: `http://localhost/`

## Troubleshooting

### Common Issues:

1. **"Not Found" errors**: Check `.htaccess` files and `mod_rewrite`
2. **Redirects fail**: Check `APP_URL` in environment variables
3. **CSS/JS not loading**: Check file permissions and paths
4. **Database connection**: Update `config/database.php`

### Debug Steps:

1. Check Apache error log: `tail -f /var/log/apache2/error.log`
2. Test PHP: `http://localhost/GSCMS/public/debug.php`
3. Check `.htaccess` is working: Create test file
4. Verify file permissions: `ls -la /var/www/html/GSCMS/public/`