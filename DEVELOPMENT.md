## Requirements
- Docker and Docker Compose
- Expose token, follow instruction here: https://expose.beyondco.de/docs/introduction to get a token

## Installation
1. Clone the repository:
```
git clone https://github.com/MultiSafepay/woocommerce.git
``` 

2. Copy the example env file and make the required configuration changes in the .env file:
```
cp .env.example .env
```
- **EXPOSE_HOST** can be set to the expose server to connect to
- **APP_SUBDOMAIN** replace the `-xx` in `woocommerce-dev-xx` with a number for example `woocommerce-dev-05`
- **EXPOSE_TOKEN** must be filled in

3. Start the Docker containers
```
docker-compose up -d
```

4. Install and activate WordPress with WooCommerce and the MultiSafepay plugin
```
make install
```
