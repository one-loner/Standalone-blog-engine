#!/bin/bash
if (($EUID !=0)); then
     echo Script must be run by root.
     exit
fi

read -p "Enter name of you blog: " n
read -p "Enter description of your blog: " d
read -p "Enter your domain: " dn

if [ -z "$n" ] || [ -z "$d" ] || [ -z "$d" ]; then
    echo "Name, decsription and domain of blog can't be empty."
    exit 1
fi

read -p "Enter login to acess your blog: " l
read -p "Enter password to acess your blog: " p

echo "Installing packages"

apt install -y debian-keyring debian-archive-keyring apt-transport-https curl php-fpm

curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg

curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list

apt update

apt install caddy

sed -i "s/Standalone blog/$n/g" index.php
sed -i "s/Decription/$d/g" index.php
sed -i "s/yourdomain.com/$dn/g" Caddyfile
hp=$(caddy hash-password --plaintext $p)
sed -i "s/userlogin/$l/g" Caddyfile
sed -i "s/userpass/$hp/g" Caddyfile
mkdir /var/www/
mkdir /var/www/html/
mkdir /var/www/html/uploads
mkdir /var/www/html/archive
mkdir /var/www/html/archive/uploads
rm /var/www/html/index.html
cp Caddyfile /etc/caddy/Caddyfile
cp www.conf /etc/php/7.4/fpm/www.conf
cp index.php /var/www/html/
cp admin.php /var/www/html/
cp archive/index.php /var/www/html/archive/
cp styles.css /var/www/html/
cp styles.css /var/www/html/archive/
chown -R www-data:www-data /var/www/html/
systemctl restart caddy
systemctl restart php-fpm
echo "Done. To manage your blog go to $dn/admin.php"

