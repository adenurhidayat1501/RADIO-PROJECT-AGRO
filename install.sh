#!/usr/bin/env bash
# ==============================================================================
# SELF-HOSTED INTERNET RADIO PLATFORM - AUTOMATED VPS INSTALLER
# Target OS: Ubuntu 24.04 LTS (Noble Numbat)
# Architecture: PHP 8.3/8.2 + MongoDB + Icecast2 + Liquidsoap + FFmpeg + Nginx
# ==============================================================================

set -e

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

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="/var/www/radio-platform"

echo -e "${BLUE}>>> Step 1: Gathering Installation Parameters...${NC}"

# Prompt for Configuration (with sensible defaults)
read -p "Enter Radio Station Name [Radio Agro]: " INPUT_STATION_NAME
STATION_NAME="${INPUT_STATION_NAME:-Radio Agro}"

read -p "Enter Primary Domain Name [radio.example.com]: " INPUT_DOMAIN
DOMAIN="${INPUT_DOMAIN:-radio.example.com}"

read -p "Enter Admin Initial Username [admin]: " INPUT_ADMIN_USER
ADMIN_USER="${INPUT_ADMIN_USER:-admin}"

read -s -p "Enter Admin Initial Password [leave blank for auto-generated]: " INPUT_ADMIN_PASS
echo ""
if [ -z "$INPUT_ADMIN_PASS" ]; then
    ADMIN_PASS=$(openssl rand -hex 6)
    echo -e "${YELLOW}Generated Random Admin Password: ${ADMIN_PASS}${NC}"
else
    ADMIN_PASS="$INPUT_ADMIN_PASS"
fi

read -p "Enter Stream Mountpoint [/live]: " INPUT_MOUNT
STREAM_MOUNT="${INPUT_MOUNT:-/live}"

read -p "Enter Stream Bitrate (64, 128, 192, 256, 320) [128]: " INPUT_BITRATE
STREAM_BITRATE="${INPUT_BITRATE:-128}"

read -p "Enter Icecast Source Password [auto-generated]: " INPUT_ICE_SRC
ICE_SRC_PASS="${INPUT_ICE_SRC:-$(openssl rand -hex 8)}"

read -p "Enter Icecast Admin Password [auto-generated]: " INPUT_ICE_ADM
ICE_ADM_PASS="${INPUT_ICE_ADM:-$(openssl rand -hex 8)}"

read -p "Enter DJ Harbor Password [auto-generated]: " INPUT_HARBOR_PASS
HARBOR_PASS="${INPUT_HARBOR_PASS:-$(openssl rand -hex 8)}"

echo -e "\n${BLUE}>>> Step 2: Updating Package Repositories & System Packages...${NC}"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https software-properties-common ufw

echo -e "${BLUE}>>> Step 3: Installing MongoDB Official Community Edition...${NC}"
# MongoDB 7.0 for Ubuntu 24.04 (or 8.0)
curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | gpg --dearmor -o /usr/share/keyrings/mongodb-server-7.0.gpg --yes
echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/7.0 multiverse" | tee /etc/apt/sources.list.d/mongodb-org-7.0.list

apt-get update -y
apt-get install -y mongodb-org || {
    echo -e "${YELLOW}Falling back to distro-packaged mongodb/mongodb-org...${NC}"
    apt-get install -y mongodb
}

systemctl daemon-reload
systemctl enable mongod || systemctl enable mongodb
systemctl restart mongod || systemctl restart mongodb

echo -e "${BLUE}>>> Step 4: Installing Nginx, PHP, Liquidsoap, Icecast2, FFmpeg, and Certbot...${NC}"
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

echo -e "${BLUE}>>> Step 5: Creating Dedicated System User & Storage Directories...${NC}"
if ! id -u radio &>/dev/null; then
    useradd -r -s /usr/sbin/nologin -d /var/lib/radio radio
fi

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
chmod -R 775 /var/lib/radio /var/log/radio /etc/radio

echo -e "${BLUE}>>> Step 6: Deploying Application Source Code & Dependencies...${NC}"
if [ "$APP_DIR" != "$TARGET_DIR" ]; then
    cp -r "$APP_DIR"/* "$TARGET_DIR"/
    cp -r "$APP_DIR"/.[!.]* "$TARGET_DIR"/ 2>/dev/null || true
fi

cd "$TARGET_DIR"

# Run Composer Install
composer install --no-dev --optimize-autoloader --no-interaction

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
chown -R www-data:www-data "$TARGET_DIR"
chown -R www-data:radio "$TARGET_DIR/storage" "$TARGET_DIR/public/uploads"
chmod -R 775 "$TARGET_DIR/storage" "$TARGET_DIR/public/uploads"

echo -e "${BLUE}>>> Step 7: Configuring Icecast2 Streaming Server...${NC}"
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

# Enable icecast2 in /etc/default/icecast2
sed -i 's/ENABLE=false/ENABLE=true/g' /etc/default/icecast2 2>/dev/null || true
systemctl restart icecast2
systemctl enable icecast2

echo -e "${BLUE}>>> Step 8: Initializing MongoDB Indexes & Seeding Administrator...${NC}"
# Run database indexes script
php "$TARGET_DIR/database/indexes.php"

# Seed Admin User in MongoDB
php -r "
require_once '$TARGET_DIR/public/index.php';
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

echo -e "${BLUE}>>> Step 9: Compiling Liquidsoap Configuration & Testing Auto DJ...${NC}"
php "$TARGET_DIR/scripts/generate-liquidsoap.php"

echo -e "${BLUE}>>> Step 10: Installing Systemd Service Units...${NC}"
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

echo -e "${BLUE}>>> Step 11: Configuring Nginx Reverse Proxy...${NC}"
# Determine PHP-FPM socket version
PHP_SOCK="/run/php/php8.3-fpm.sock"
if [ ! -S "$PHP_SOCK" ]; then
    PHP_SOCK=$(find /run/php/ -name "php*-fpm.sock" | head -n 1)
fi

sed -e "s|radio.yourdomain.com|${DOMAIN}|g" \
    -e "s|stream.yourdomain.com|stream.${DOMAIN}|g" \
    -e "s|unix:/run/php/php8.3-fpm.sock|unix:${PHP_SOCK}|g" \
    "$TARGET_DIR/nginx/radio.conf" > /etc/nginx/sites-available/radio.conf

ln -sf /etc/nginx/sites-available/radio.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

nginx -t
systemctl restart nginx

echo -e "${BLUE}>>> Step 12: Configuring Firewall (UFW)...${NC}"
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8000/tcp # Icecast Direct
ufw allow 8005/tcp # DJ Live Harbor
ufw --force enable

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
EOF

chmod +x install.sh
