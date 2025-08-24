# Coding Challenge Assignment in PHP Laravel

## Execute the app

Clone the project and follow these commands in order to run the project:

1. ** Change the dir path to project folder and open the terminal **

2. ** Copy the `.env.example` file to `.env`**

     ```bash
     cp .env.example .env
     php artisan key:generate
     ```

3. ** Install all PHP dependencies **
     ```bash
    composer install
    ```

4. ** Now run following Laravel console command in the terminal **
    
   ```bash
    php artisan report:generate
    ```
   
   - It will give you a guidance how generate reports like below
   ```bash
    Assessment Report Generator
    ==========================
    
    Available Report Types:
       1. Diagnostic Report
       2. Progress Report
       3. Feedback Report
    
    Usage:
    php artisan report:generate <studentId> <reportType>
    php artisan report:generate student123 1
    
    Options:
    --list    Show available report types
    
    Examples:
    php artisan report:generate student123 1
    php artisan report:generate --list

    ```
5. ** Run feature tests **
     ```bash
    php artisan test
   
    ./vendor/bin/phpunit tests/Feature/Services/GenerateReportCommandTest.php
   
    ./vendor/bin/phpunit tests/Feature/Services/GenerateReportCommandTest.php --filter <test_function_name>
    ```
