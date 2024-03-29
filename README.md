# Laravel Automated-Trading
The laravel scaffolding of our projects.

## Deploying To Local Server
If you use valet just execute the `init.sh` file to configure your environment automatically.
```bash
git clone https://github.com/Yousef-Hatem/Automated-Trading.git Automated-Trading
cd Automated-Trading
bash ./init.sh
```
Otherwise, you should configure your environment manually by the following steps:

- Clone the project to your local server using the following command:
    ```bash
    git clone https://github.com/Yousef-Hatem/Automated-Trading.git Automated-Trading
    ```

- Go to the project path and configure your environment:
    - Copy the `.env.example` file to `.env`:
        ```bash
        cd ./Automated-Trading
    
        cp .env.example .env
        ```
    - Configure database in your `.env` file:
        ```dotenv
        DB_DATABASE=project
        DB_USERNAME=root
        DB_PASSWORD=
        ```
    - Install composer packages using the following command:
        ```bash
        composer install
        ```
    - Migrate the database tables and dummy data:
        ```bash
        php artisan migrate --seed
        ```
        > After migrating press `Y` to seed dummy data.
    - Run the project in your browser using `artisan serve` command:
        ```bash
        php artisan serve
        ```
    - Go to your browser and visit: [http://localhost:8000](http://localhost:8000)
