### `Coppy file .env.example => .env`
### `FRONT_URL= trong .env là frontend url`

### `Chạy cái này đầu tiên để build lần đầu`
### docker-compose up -d --build

### `Stop`
### docker-compose stop

### `Start`
### docker-compose start
### `Cái này chạy nginx này`

### docker-compose exec app sh
### `Lần đầu thì install, mỗi lần update code thì dùng update`
- composer install
- composer update

### `Vẫn đứng trong nginx chạy cái này`
- php artisan key:generate
- php artisan migrate:reset
- php artisan migrate
- php artisan passport:install
- php artisan passport:keys --force

### `Clear nếu bị cache gây ra lỗi`
- php artisan cache:clear
- php artisan config:clear
- php artisan route:clear
- php artisan view:clear

### `Linux phải cấp quyền cho storage`
- chmod -R 0777 storage

## `URL`
- **[URL](http://localhost:8000)**  `http://localhost:8000`
- **[URL API](http://localhost:8000)**  `http://localhost:8000/api`
