include .env
export

.PHONY: install
install: install-wordpress \
		install-woocommerce \
		initialize-woocommerce \
		install-woocommerce-default-pages \
		import-sample-products \
		install-storefront-theme \
		install-multisafepay

.PHONY: install-wordpress
install-wordpress:
	docker-compose exec app wp core install \
							   --url="${APP_SUBDOMAIN}.${EXPOSE_HOST}" \
							   --title="${APP_SUBDOMAIN}" \
							   --admin_user="admin" \
							   --admin_password="admin" \
							   --admin_email="example@multisafepay.com" \
							   --skip-email

.PHONY: install-woocommerce
install-woocommerce:
	docker-compose exec app wp plugin install woocommerce --activate

.PHONY: initialize-woocommerce
initialize-woocommerce:
	docker-compose exec app wp option set woocommerce_store_address "Kraanspoor 39"
	docker-compose exec app wp option set woocommerce_store_address_2 ""
	docker-compose exec app wp option set woocommerce_store_city "Amsterdam"
	docker-compose exec app wp option set woocommerce_default_country "NL:*"
	docker-compose exec app wp option set woocommerce_store_postcode "1033SC"
	docker-compose exec app wp option set woocommerce_currency "EUR"
	docker-compose exec app wp option set woocommerce_product_type "both"
	docker-compose exec app wp option set woocommerce_allow_tracking "no"

	docker-compose exec app wp option set --format=json woocommerce_stripe_settings '{"enabled":"no","create_account":false,"email":false}'
	docker-compose exec app wp option set --format=json woocommerce_klarna_checkout_settings '{"enabled":"no"}'
	docker-compose exec app wp option set --format=json woocommerce_ppec_paypal_settings '{"reroute_requests":false,"email":false}'
	docker-compose exec app wp option set --format=json woocommerce_cheque_settings '{"enabled":"yes"}'
	docker-compose exec app wp option set --format=json woocommerce_bacs_settings '{"enabled":"yes"}'
	docker-compose exec app wp option set --format=json woocommerce_cod_settings '{"enabled":"yes"}'

	docker-compose exec app wp option set --format=json woocommerce_flat_rate_1_settings '{"title":"Flat rate","tax_status":"taxable","cost":"4.95"}'
	docker-compose exec app wp option set --format=json woocommerce_flat_rate_2_settings '{"title":"Flat rate","tax_status":"taxable","cost":"3.95"}'

.PHONY: install-woocommerce-default-pages
install-woocommerce-default-pages:
	docker-compose exec app wp wc --user=1 tool run install_pages

.PHONY: import-sample-products
import-sample-products:
	docker-compose exec app wp plugin install wordpress-importer --activate
	docker-compose exec app wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=create --quiet

.PHONY: install-storefront-theme
install-storefront-theme:
	docker-compose exec app wp theme install storefront --activate

.PHONY: install-multisafepay
install-multisafepay:
	docker-compose exec --workdir /var/www/html/wp-content/plugins/multisafepay app composer install
	docker-compose exec app wp plugin activate multisafepay --user=1

.PHONY: composer-update
composer-update:
	docker-compose exec --workdir /var/www/html/wp-content/plugins/multisafepay app composer update

.PHONY: phpcs
phpcs:
	docker-compose exec --workdir /var/www/html/wp-content/plugins/multisafepay app composer run-script phpcs

.PHONY: phpcbf
phpcbf:
	docker-compose exec --workdir /var/www/html/wp-content/plugins/multisafepay app composer run-script phpcbf

.PHONY: phpstan
phpstan:
	docker-compose exec --workdir /var/www/html/wp-content/plugins/multisafepay app composer run-script phpstan
