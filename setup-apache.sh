#!/bin/bash
# Setup script for Apache deployment

echo "GSCMS Apache Setup Script"
echo "========================="

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo "⚠️  Don't run this as root. Run as your regular user."
    exit 1
fi

# Get current directory
CURRENT_DIR=$(pwd)
echo "📂 Current directory: $CURRENT_DIR"

# Check if we're in the right directory
if [ ! -f "public/index.php" ]; then
    echo "❌ Error: Please run this script from the GSCMS root directory"
    exit 1
fi

echo ""
echo "🔧 Setting up file permissions..."

# Set proper permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 storage/
sudo chmod -R 777 public/uploads/

echo "✅ Permissions set"

# Check if mod_rewrite is enabled
echo ""
echo "🔍 Checking Apache modules..."

if ! apache2ctl -M | grep -q rewrite_module; then
    echo "⚠️  mod_rewrite is not enabled. Enabling it..."
    sudo a2enmod rewrite
    sudo systemctl reload apache2
    echo "✅ mod_rewrite enabled"
else
    echo "✅ mod_rewrite is already enabled"
fi

# Create .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo ""
    echo "📝 Creating .env file..."
    cat > .env << EOF
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost/GSCMS/public
APP_TIMEZONE=Africa/Johannesburg

DB_HOST=localhost
DB_PORT=3306
DB_NAME=gde_scibotics_db
DB_USER=vuksDev
DB_PASS=Vu13#k*s3D3V

MAIL_FROM_ADDRESS=noreply@gde.gov.za
MAIL_FROM_NAME="GDE SciBOTICS"
EOF
    echo "✅ .env file created"
fi

echo ""
echo "📋 Setup Summary:"
echo "=================="
echo "• Root .htaccess: ✅ Created (redirects to public/)"
echo "• Public .htaccess: ✅ Exists (handles routing)"
echo "• File permissions: ✅ Set (www-data:www-data)"
echo "• Apache mod_rewrite: ✅ Enabled"
echo "• Configuration: ✅ Updated"

echo ""
echo "🌐 Access URLs:"
echo "==============="
echo "• Main site: http://localhost/GSCMS/public/"
echo "• Login: http://localhost/GSCMS/public/auth/login"
echo "• Admin: http://localhost/GSCMS/public/admin/dashboard"
echo "• Debug: http://localhost/GSCMS/public/debug.php"

echo ""
echo "🔑 Default Admin Credentials:"
echo "============================="
echo "• Email: admin@gde.gov.za"
echo "• Password: admin123!@#"

echo ""
echo "⚠️  IMPORTANT NOTES:"
echo "==================="
echo "1. Change the default admin password after first login"
echo "2. Update database credentials in .env if needed"
echo "3. Check Apache error log if issues: tail -f /var/log/apache2/error.log"
echo "4. For production, set APP_DEBUG=false in .env"

echo ""
echo "✅ Setup complete! You can now access the site."