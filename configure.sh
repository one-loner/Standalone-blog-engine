#!/bin/bash
read -p "Enter name of you blog: " n
read -p "Enter description of your blog: " d
if [ -z "$n" ] || [ -z "$d" ]; then
    echo "Name and decsription of blog can't be empty."
    exit 1
fi

sed -i "s/Standalone blog/$n/g" index.php
sed -i "s/Decription/$d/g" index.php
sed -i "s/Standalone blog/$n/g" admin.php
echo "Done. To manage your blog go to <blog.url>/admin.php"

