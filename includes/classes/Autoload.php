<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

class MultiSafepay_Autoload
{

    public static function register()
    {
        spl_autoload_register(array('self', 'spl_autoload_register'));
    }

    public static function spl_autoload_register($class_name)
    {

        if (substr($class_name, 0, 12) !== 'MultiSafepay') {
            return;
        }

        $file_name = dirname(__FILE__) . '/' . str_replace('_', '/', $class_name) . '.php';
        if (file_exists($file_name)) {
            require_once $file_name;
            return;
        }

        $class_name = str_replace("Object", "Object/", $class_name);
        $file_name  = dirname(__FILE__) . '/' . str_replace('_', '/api/', $class_name) . '.php';
        if (file_exists($file_name)) {
            require_once $file_name;
            return;
        }else{

            $log = "FATAL!!  $class_name from $file_name.\n";
            $string = sprintf ("%s\n%s\n%s\n%s: %s\n\n",  date ('Y-m-d H:i:s'), __FILE__ , __METHOD__ , 'DEBUG:',  $log);
            error_log($string, 3, "MultiSafepay.log");

        }
    }
}
