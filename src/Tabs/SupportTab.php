<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce\Tabs;


class SupportTab
{
    /**
     * SupportTab constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_settings_tabs_multisafepay_support', [self::class, 'fillSupportTab']);
    }


    /**
     * Fill the support tab page
     *
     * @return void
     */
    public static function fillSupportTab(): void
    {
        echo
        '
        <style>
        .multisafepay-ul li{
           margin-left: 2rem; 
        }
        
        .mt-2 {
            margin-top: 2rem;
        }
        .mt-1 {
            margin-top: 1rem;
        }
        </style>
         <div id="multisafepay-support">
        <h2 class="multisafepay-title">Documentation</h2>
        <p>Read our documentation for more information about MultiSafepay and how to get started:</p>
        <ul class="multisafepay-ul">
            <li>
                <a href="https://docs.multisafepay.com/" target="_blank" rel="noopener">
                    Manual
                </a>
            </li>
            <li>
                <a href="https://github.com/MultiSafepay/Craft-plugin-php-internal/blob/master/CHANGELOG.md"
                   target="_blank" rel="noopener">
                    Changelog
                </a>
            </li>
            <li>
                <a href="https://docs.multisafepay.com/" target="_blank" rel="noopener">
                    FAQ
                </a>
            </li>
        </ul>
        <p class="mt-1">For developers:</p>
        <ul class="multisafepay-ul">
            <li>
                <a href="https://docs.multisafepay.com/support-tab/api" target="_blank" rel="noopener">
                    API Documentation
                </a>
            </li>
            <li>
                <a href="https://github.com/MultiSafepay/Craft-plugin-php-internal" target="_blank" rel="noopener">
                    MultiSafepay Github
                </a>
            </li>
        </ul>
        <h2 class="mt-2">Account</h2>
        <p>
            To use this plugin you need a MultiSafepay account.
            <br/>
            If you would like to have a clear overview of what MultiSafepay has to offer, feel free to create a
            <a href="https://testmerchant.multisafepay.com/signup" target="_blank" rel="noopener">
                test account
            </a>
            .
        </p>
        <p class="mt-2">
            If you would like to set up a live account, please contact the MultiSafepay sales department:
        </p>
        <table style="border: 0">
            <tbody>
            <tr>
                <td>Telephone:</td>
                <td>
                    <a href="tel:+31208500501">
                        +31 (0)20 - 8500501
                    </a>
                </td>
            </tr>
            <tr>
                <td>E-mail:</td>
                <td>
                    <a href="mailto:sales@multisafepay.com">
                        sales@multisafepay.com
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
        <h2 class="mt-2">Contact</h2>
        <p>
            Need assistance? Feel free to contact our integration team:
        </p>
        <table style="border: 0">
            <tbody>
            <tr>
                <td>Telephone:</td>
                <td>
                    <a href="tel:+31208500500">
                        +31 (0)20 - 8500500
                    </a>
                </td>
            </tr>
            <tr>
                <td>E-mail:</td>
                <td>
                    <a href="mailto:integration@multisafepay.com">
                        integration@multisafepay.com
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
        ';
    }
}
