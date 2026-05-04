/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @package     MultiSafepay
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

// Ensure any global CSS overrides are injected as early as possible.
import './lib/wallet/googlePayStyles';

import { bootstrapMultisafepayBlocksPaymentMethods } from './lib/register/registerPaymentMethods';

/**
 * @file Blocks bundle entrypoint.
 *
 * Side effects:
 * - Imports a side effect module that injects early CSS overrides for Google Pay button rendering in Blocks.
 * - Registers MultiSafepay payment methods into the WooCommerce Blocks registry.
 */

/**
 * Bootstraps payment-method registration.
 *
 * This is intentionally executed immediately on the module load because Woo Blocks expects
 * payment methods to register as soon as the registry is available.
 *
 * @returns {void}
 */
bootstrapMultisafepayBlocksPaymentMethods();

// phpcs:enable
