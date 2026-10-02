#!/usr/bin/env bash
# ==============================================================================
# SELF-HOSTED INTERNET RADIO PLATFORM - AUTOMATED VPS INSTALLER
# Target OS: Ubuntu 24.04 LTS (Noble Numbat)
# Architecture: PHP 8.3 + MongoDB 8.0 + Icecast2 + Liquidsoap 2.2 + FFmpeg + Nginx
# ==============================================================================

set -eo pipefail

# ANSI Color Codes
GREEN="\033[1;32m"
BLUE="\033[1;34m"
YELLOW="\033[1;33m"
RED="\033[1;31m"
CYAN="\033[1;36m"
NC="\033[0m"

echo -e "${CYAN}==============================================================================${NC}"
echo -e "${GREEN}      SELF-HOSTED INTERNET RADIO PLATFORM - INSTALLER (UBUNTU 24.04)${NC}"
echo -e "${CYAN}==============================================================================${NC}"

# Check for root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] This installer must be run as root (or with sudo). Exiting.${NC}"
    exit 1
fi

# Detect Ubuntu Version
if [ -f /etc/os-release ]; then
    . /etc/os-release
    if [ "$ID" != "ubuntu" ]; then
        echo -e "${YELLOW}[WARNING] This script is optimized for Ubuntu 24.04 LTS. Detected OS: ${PRETTY_NAME}${NC}"
    fi
fi

UBUNTU_CODENAME=$(lsb_release -cs 2>/dev/null || echo "noble")
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="/var/www/radio-platform"

echo -e "\n${BLUE}>>> Step 1: Gathering Installation Parameters...${NC}"

# Support non-interactive mode or environment variable overrides
if [ -z "${STATION_NAME:-}" ]; then
    read -p "Enter Radio Station Name [Radio Agro]: " INPUT_STATION_NAME
    STATION_NAME="${INPUT_STATION_NAME:-Radio Agro}"
fi

if [ -z "${DOMAIN:-}" ]; then
    read -p "Enter Primary Domain Name [radio.example.com]: " INPUT_DOMAIN
    DOMAIN="${INPUT_DOMAIN:-radio.example.com}"
fi

if [ -z "${ADMIN_USER:-}" ]; then
    read -p "Enter Admin Initial Username [admin]: " INPUT_ADMIN_USER
    ADMIN_USER="${INPUT_ADMIN_USER:-admin}"
fi

if [ -z "${ADMIN_PASS:-}" ]; then
    read -s -p "Enter Admin Initial Password [leave blank for auto-generated]: " INPUT_ADMIN_PASS
    echo ""
    if [ -z "$INPUT_ADMIN_PASS" ]; then
        ADMIN_PASS=$(openssl rand -hex 6)
        echo -e "${YELLOW}Generated Random Admin Password: ${ADMIN_PASS}${NC}"
    else
        ADMIN_PASS="$INPUT_ADMIN_PASS"
    fi
fi

if [ -z "${STREAM_MOUNT:-}" ]; then
    read -p "Enter Stream Mountpoint [/live]: " INPUT_MOUNT
    STREAM_MOUNT="${INPUT_MOUNT:-/live}"
fi

if [ -z "${STREAM_BITRATE:-}" ]; then
    read -p "Enter Stream Bitrate (64, 128, 192, 256, 320) [128]: " INPUT_BITRATE
    STREAM_BITRATE="${INPUT_BITRATE:-128}"
fi

ICE_SRC_PASS="${ICE_SRC_PASS:-$(openssl rand -hex 8)}"
ICE_ADM_PASS="${ICE_ADM_PASS:-$(openssl rand -hex 8)}"
HARBOR_PASS="${HARBOR_PASS:-$(openssl rand -hex 8)}"

echo -e "\n${BLUE}>>> Step 2: Updating Package Repositories & System Packages...${NC}"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https software-properties-common ufw

# Ensure universe repository is active on Ubuntu 24.04 (contains icecast2 & liquidsoap)
add-apt-repository -y universe
apt-get update -y

echo -e "\n${BLUE}>>> Step 3: Installing MongoDB Official Community Edition (Ubuntu 24.04 Noble)...${NC}"
# MongoDB 8.0 is the official LTS release built natively for Ubuntu 24.04 (Noble)
curl -fsSL https://pgp.mongodb.com/server-8.0.asc | gpg --dearmor -o /usr/share/keyrings/mongodb-server-8.0.gpg --yes
echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-8.0.gpg ] https://repo.mongodb.org/apt/ubuntu ${UBUNTU_CODENAME}/mongodb-org/8.0 multiverse" | tee /etc/apt/sources.list.d/mongodb-org-8.0.list

apt-get update -y
apt-get install -y mongodb-org mongodb-database-tools || {
    echo -e "${YELLOW}Falling back to MongoDB 7.0 repository...${NC}"
    curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | gpg --dearmor -o /usr/share/keyrings/mongodb-server-7.0.gpg --yes
    echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/ubuntu ${UBUNTU_CODENAME}/mongodb-org/7.0 multiverse" | tee /etc/apt/sources.list.d/mongodb-org-7.0.list
    apt-get update -y
    apt-get install -y mongodb-org mongodb-database-tools
}

systemctl daemon-reload
systemctl enable mongod
systemctl restart mongod

# Verify MongoDB is running
if ! systemctl is-active --quiet mongod; then
    echo -e "${RED}[ERROR] MongoDB service failed to start. Check /var/log/mongodb/mongod.log for details.${NC}"
    exit 1
fi
echo -e "${GREEN}MongoDB service is active and running.${NC}"

echo -e "\n${BLUE}>>> Step 4: Installing Nginx, PHP 8.3, Liquidsoap, Icecast2, FFmpeg, and Certbot...${NC}"
apt-get install -y \
    nginx \
    php-fpm php-cli php-mongodb php-curl php-xml php-mbstring php-zip php-gd \
    icecast2 \
    liquidsoap \
    ffmpeg \
    certbot python3-certbot-nginx \
    unzip git

# Install Composer if not present
if ! command -v composer &> /dev/null; then
    echo -e "${CYAN}Installing Composer globally...${NC}"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Detect installed PHP version (Ubuntu 24.04 default is 8.3)
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "8.3")
echo -e "${CYAN}Detected PHP Version: ${PHP_VER}${NC}"

# Optimize PHP-FPM and CLI php.ini for Audio Uploads (up to 128MB per track)
for PHP_INI in "/etc/php/${PHP_VER}/fpm/php.ini" "/etc/php/${PHP_VER}/cli/php.ini"; do
    if [ -f "$PHP_INI" ]; then
        echo -e "Tuning upload limits in ${PHP_INI}..."
        sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 128M/' "$PHP_INI"
        sed -i 's/^post_max_size = .*/post_max_size = 128M/' "$PHP_INI"
        sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
        sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    fi
done

# Restart PHP-FPM service to apply changes
systemctl enable "php${PHP_VER}-fpm" || systemctl enable php-fpm || true
systemctl restart "php${PHP_VER}-fpm" || systemctl restart php-fpm || true

echo -e "\n${BLUE}>>> Step 5: Creating Dedicated System User & Storage Directories...${NC}"
if ! id -u radio &>/dev/null; then
    useradd -r -s /usr/sbin/nologin -d /var/lib/radio radio
fi

# Add www-data and radio to each other's groups for seamless file and log sharing
usermod -a -G radio www-data
usermod -a -G www-data radio

mkdir -p /var/lib/radio/music
mkdir -p /var/lib/radio/jingles
mkdir -p /var/lib/radio/ads
mkdir -p /var/lib/radio/fallback
mkdir -p /var/lib/radio/playlists
mkdir -p /etc/radio
mkdir -p /var/log/radio
mkdir -p "$TARGET_DIR"

# Generate emergency broadcast safety tone (440Hz sine wave 10s looped)
if [ ! -f /var/lib/radio/fallback/default.mp3 ]; then
    echo -e "${CYAN}Generating safety emergency fallback audio track...${NC}"
    ffmpeg -y -f lavfi -i "sine=frequency=440:duration=10" -c:a libmp3lame -b:a 128k /var/lib/radio/fallback/default.mp3 &>/dev/null
fi

chown -R radio:radio /var/lib/radio /var/log/radio /etc/radio
chmod -R 2775 /var/lib/radio /var/log/radio /etc/radio

echo -e "\n${BLUE}>>> Step 6: Deploying Application Source Code & Dependencies...${NC}"
if [ "$APP_DIR" != "$TARGET_DIR" ]; then
    echo -e "Copying application source from ${APP_DIR} to ${TARGET_DIR}..."
    mkdir -p "$TARGET_DIR"
    cp -a "$APP_DIR"/. "$TARGET_DIR"/
fi

cd "$TARGET_DIR"

# Set executable flags on scripts
chmod +x "$TARGET_DIR"/scripts/*.php "$TARGET_DIR"/database/*.php 2>/dev/null || true

# Run Composer Install
export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_NO_BLOCKING=1
composer config policy.advisories.block false 2>/dev/null || true
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-mongodb

# Create .env file
APP_SECRET=$(openssl rand -hex 16)
cat > "$TARGET_DIR/.env" <<EOF
APP_NAME="${STATION_NAME}"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://${DOMAIN}
APP_SECRET=${APP_SECRET}

# Database Configuration (MongoDB ONLY)
MONGODB_URI=mongodb://127.0.0.1:27017
MONGODB_DATABASE=radio_platform

# Station Identity
STATION_NAME="${STATION_NAME}"
STATION_DESCRIPTION="Radio Komunitas Mandiri & Musik 24/7"
STATION_GENRE="Agro, News, Dangdut & Pop Nusantara"
STATION_LANGUAGE=id
STATION_COUNTRY=ID
TIMEZONE=Asia/Jakarta

# Icecast2 Streaming Server
ICECAST_HOST=127.0.0.1
ICECAST_PORT=8000
ICECAST_MOUNTPATH=${STREAM_MOUNT}
ICECAST_ADMIN_USER=admin
ICECAST_ADMIN_PASSWORD=${ICE_ADM_PASS}
ICECAST_SOURCE_PASSWORD=${ICE_SRC_PASS}
ICECAST_PUBLIC_URL=http://${DOMAIN}${STREAM_MOUNT}

# Liquidsoap Auto DJ & Live Harbor Engine
LIQUIDSOAP_HOST=127.0.0.1
LIQUIDSOAP_TELNET_PORT=1234
LIQUIDSOAP_HARBOR_PORT=8005
LIQUIDSOAP_HARBOR_MOUNT=/live-dj
LIQUIDSOAP_HARBOR_USER=source
LIQUIDSOAP_HARBOR_PASSWORD=${HARBOR_PASS}

# Audio Storage Directories
AUDIO_STORAGE_PATH=/var/lib/radio/music
JINGLE_STORAGE_PATH=/var/lib/radio/jingles
AD_STORAGE_PATH=/var/lib/radio/ads
FALLBACK_AUDIO_PATH=/var/lib/radio/fallback/default.mp3

# Stream Profile
RADIO_BITRATE=${STREAM_BITRATE}
RADIO_FORMAT=mp3

SESSION_LIFETIME=86400
EOF

# Ensure writable permissions for web and radio user
mkdir -p "$TARGET_DIR/storage/logs" "$TARGET_DIR/storage/backups" "$TARGET_DIR/public/uploads"
chown -R www-data:www-data "$TARGET_DIR"
chown -R www-data:radio "$TARGET_DIR/storage" "$TARGET_DIR/public/uploads"
chmod -R 2775 "$TARGET_DIR/storage" "$TARGET_DIR/public/uploads"

# Configure sudoers rule so www-data can reload/restart radio-liquidsoap safely from Web UI
echo -e "Configuring sudoers permissions for www-data service control..."
cat > /etc/sudoers.d/radio-platform <<'SUDO_EOF'
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload radio-liquidsoap, /usr/bin/systemctl restart radio-liquidsoap, /usr/bin/systemctl status radio-liquidsoap, /usr/bin/systemctl restart radio-scheduler, /usr/bin/systemctl restart radio-sync, /usr/bin/systemctl status radio-scheduler, /usr/bin/systemctl status radio-sync
SUDO_EOF
chmod 440 /etc/sudoers.d/radio-platform

echo -e "\n${BLUE}>>> Step 7: Configuring Icecast2 Streaming Server...${NC}"
cat > /etc/icecast2/icecast.xml <<EOF
<icecast>
    <location>Indonesia</location>
    <admin>admin@${DOMAIN}</admin>
    <limits>
        <clients>2000</clients>
        <sources>10</sources>
        <queue-size>524288</queue-size>
        <client-timeout>30</client-timeout>
        <header-timeout>15</header-timeout>
        <source-timeout>10</source-timeout>
        <burst-on-connect>1</burst-on-connect>
        <burst-size>65535</burst-size>
    </limits>
    <authentication>
        <source-password>${ICE_SRC_PASS}</source-password>
        <relay-password>${ICE_SRC_PASS}</relay-password>
        <admin-user>admin</admin-user>
        <admin-password>${ICE_ADM_PASS}</admin-password>
    </authentication>
    <hostname>${DOMAIN}</hostname>
    <listen-socket>
        <port>8000</port>
        <bind-address>0.0.0.0</bind-address>
    </listen-socket>
    <mount type="normal">
        <mount-name>${STREAM_MOUNT}</mount-name>
        <fallback-mount>/fallback</fallback-mount>
        <fallback-override>1</fallback-override>
        <bitrate>${STREAM_BITRATE}</bitrate>
        <type>audio/mpeg</type>
        <subtype>mp3</subtype>
    </mount>
    <fileserve>1</fileserve>
    <paths>
        <logdir>/var/log/icecast2</logdir>
        <webroot>/usr/share/icecast2/web</webroot>
        <adminroot>/usr/share/icecast2/admin</adminroot>
    </paths>
    <logging>
        <accesslog>access.log</accesslog>
        <errorlog>error.log</errorlog>
        <loglevel>3</loglevel>
    </logging>
    <security>
        <chroot>0</chroot>
    </security>
</icecast>
EOF

# Ensure icecast2 permissions
chown -R icecast2:icecast /etc/icecast2 /var/log/icecast2
chmod 640 /etc/icecast2/icecast.xml

# Enable icecast2 in /etc/default/icecast2
sed -i 's/ENABLE=false/ENABLE=true/g' /etc/default/icecast2 2>/dev/null || true
systemctl restart icecast2
systemctl enable icecast2

echo -e "\n${BLUE}>>> Step 8: Initializing MongoDB Indexes & Seeding Administrator...${NC}"
# Run database indexes script
php "$TARGET_DIR/database/indexes.php"

# Seed Admin User in MongoDB
php -r "
require_once '$TARGET_DIR/bootstrap.php';
use App\Models\User;
\$existing = User::findOne(['username' => '${ADMIN_USER}']);
if (!\$existing) {
    User::createUser([
        'username' => '${ADMIN_USER}',
        'email' => 'admin@${DOMAIN}',
        'display_name' => 'Administrator',
        'password' => '${ADMIN_PASS}',
        'role' => 'admin',
        'status' => 'active',
    ]);
    echo \"Admin user created successfully.\n\";
} else {
    User::updatePassword((string)\$existing['_id'], '${ADMIN_PASS}');
    echo \"Admin password updated.\n\";
}
"

echo -e "\n${BLUE}>>> Step 9: Compiling Liquidsoap Configuration & Testing Auto DJ...${NC}"
php "$TARGET_DIR/scripts/generate-liquidsoap.php"
chown radio:radio /etc/radio/radio.liq
chmod 664 /etc/radio/radio.liq

echo -e "\n${BLUE}>>> Step 10: Installing Systemd Service Units...${NC}"
cp "$TARGET_DIR/systemd/radio-liquidsoap.service" /etc/systemd/system/
cp "$TARGET_DIR/systemd/radio-scheduler.service" /etc/systemd/system/
cp "$TARGET_DIR/systemd/radio-sync.service" /etc/systemd/system/

systemctl daemon-reload
systemctl enable radio-liquidsoap.service
systemctl enable radio-scheduler.service
systemctl enable radio-sync.service

systemctl restart radio-liquidsoap.service
systemctl restart radio-scheduler.service
systemctl restart radio-sync.service

echo -e "\n${BLUE}>>> Step 11: Configuring Nginx Reverse Proxy...${NC}"
# Determine PHP-FPM socket version
PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
if [ ! -S "$PHP_SOCK" ]; then
    FOUND_SOCK=$(find /run/php/ -name "php*-fpm.sock" | head -n 1)
    if [ -n "$FOUND_SOCK" ]; then
        PHP_SOCK="$FOUND_SOCK"
    fi
fi

sed -e "s|radio.yourdomain.com|${DOMAIN}|g" \
    -e "s|stream.yourdomain.com|stream.${DOMAIN}|g" \
    -e "s|unix:/run/php/php8.3-fpm.sock|unix:${PHP_SOCK}|g" \
    "$TARGET_DIR/nginx/radio.conf" > /etc/nginx/sites-available/radio.conf

ln -sf /etc/nginx/sites-available/radio.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

nginx -t
systemctl restart nginx

echo -e "\n${BLUE}>>> Step 12: Configuring Firewall (UFW)...${NC}"
ufw allow 80/tcp || true
ufw allow 443/tcp || true
ufw allow 8000/tcp || true # Icecast Direct
ufw allow 8005/tcp || true # DJ Live Harbor
ufw --force enable || true

echo -e "\n${CYAN}==============================================================================${NC}"
echo -e "${GREEN}      RADIO PLATFORM INSTALLATION COMPLETED SUCCESSFULLY!${NC}"
echo -e "${CYAN}==============================================================================${NC}"
echo -e "Web Portal:         ${GREEN}http://${DOMAIN}${NC}"
echo -e "Admin Panel:        ${GREEN}http://${DOMAIN}/admin${NC}"
echo -e "Stream URL:         ${GREEN}http://${DOMAIN}${STREAM_MOUNT}${NC}"
echo -e "Stream Direct:      ${GREEN}http://${DOMAIN}:8000${STREAM_MOUNT}${NC}"
echo -e "Admin Username:     ${YELLOW}${ADMIN_USER}${NC}"
echo -e "Admin Password:     ${YELLOW}${ADMIN_PASS}${NC}"
echo -e "DJ Harbor Port:     ${YELLOW}8005${NC} (Mount: /live-dj, User: source, Pass: ${HARBOR_PASS})"
echo -e "Icecast Passwords:  Source: ${ICE_SRC_PASS} | Admin: ${ICE_ADM_PASS}"
echo -e "${CYAN}------------------------------------------------------------------------------${NC}"
echo -e "To configure SSL HTTPS with Let's Encrypt, run:"
echo -e "  ${YELLOW}certbot --nginx -d ${DOMAIN}${NC}"
echo -e "${CYAN}==============================================================================${NC}\n"
