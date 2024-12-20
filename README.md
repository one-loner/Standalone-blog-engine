# Standelone blog engine    
Простой движок для standelone блога.   
Необходим веб сервер с поддержкой php   
Для корректной работы в корневой папке должны находиться папка uploads,styles.css, index.php, admin.php и posts.html    
Владельцами файлов и папок должны быть пользователь и группа www-data   
Управление осуществляется через /admin.php   
Чтобы избежаеть несанкционированного доступа переименуйте admin.php,   

   
После чего копируем папку uploads,styles.css, index.php, admin.php и posts.html в /var/www/html/  
Не забываем выставить владельцем www-data   
chown -R www-data:www-data /var/www/html/    
## Настройка прав доступа       
Вы можете перименовать admin.php для защиты доступа    
Так же вы можете защитить /admin.php средствами web-сервера, например, в конфигурацию apache нужно добавить следующее:  
Пример для Apache:    
        <Files "admin.php" "archive.php>      
        AuthType Basic   
        AuthName "Restricted Area"   
        AuthUserFile /etc/apache2/.htpasswd   
        Require valid-user   
        </Files>   

   
Логин и пароль для доступа устанавливаются следующей командой (от root):   
htpasswd -c /etc/apache2/.htpasswd имя_пользователя   
Добавил в репозиторий для примера файл 000-default.conf    

Для Caddy:    


yourdomain.com {   
    root * /path/to/your/site   
    php_fastcgi 127.0.0.1:9000   
    file_server   
      
    # Защита доступа к admin.php и archive.php        
    basicauth /admin.php /archive.php {       
        userlogin hashed_password       
    }       
}     

Для получения хешированного пароля: caddy hash-password --plaintext yourpassword  
       
Для автоматической установки и настройки Caddy в репозитории есть скрипт configure.sh     

