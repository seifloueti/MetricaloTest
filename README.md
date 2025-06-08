# MetricaloTest
MetricaloTest

## 🚀 Quick Start

### 1. Clone the repository


git clone <your-repo-url>
cd <project-directory>


all the command we need we its under the makefile can run it by the maker

### 2. Set Up the Environment


Using make 

    make rebuild

Or using Docker manually

    docker compose stop
	docker compose pull
	docker compose rm --force app
	docker compose build --no-cache --pull
	docker compose up -d --force-recreate

the project runs at: http://localhost:8000

### 3. Access the Application Container

Using make

    make shell

Or using Docker directly

	docker compose exec --user application app /bin/bash

### 4.  Install Dependencies

composer install

### 5. Test the API


ACI Payment Endpoint
POST http://localhost:8000/app/payment/aci

Shift4 Payment Endpoint
POST http://localhost:8000/app/payment/shift4

Request Body Example (JSON):

{
"amount": "50.50",
"currency": "USD",
"card_number": "4242424242424242",
"card_exp_month": 6,
"card_exp_year": 2025,
"card_cvv": "123"
}



### 6. Run the Command-Line Payment Processor

php bin/console app:payment aci 50 USD 4242424242424242 6 2025 123
or
php bin/console app:payment shift4 50 USD 4242424242424242 6 2025 123


### 7. Run Tests

php bin/phpunit
