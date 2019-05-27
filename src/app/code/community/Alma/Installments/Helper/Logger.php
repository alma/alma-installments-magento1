<?php
/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

if (class_exists('Psr\Log\AbstractLogger', false) == false) {
    require_once Mage::getBaseDir('lib') . '/Alma_Installments/autoload.php';
}

use Psr\Log\LogLevel;

class Alma_Installments_Helper_Logger extends Mage_Core_Helper_Abstract
{
    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        static $logger;

        if (!$logger) {
            $logger = new AlmaLogger(Mage::helper('alma/config'));
        }

        return $logger;
    }
}

class AlmaLogger extends \Psr\Log\AbstractLogger
{
    /**
     * @var Alma_Installments_Helper_Config
     */
    private $config;

    public function __construct(Alma_Installments_Helper_Config $config)
    {

        $this->config = $config;
    }

    public function log($level, $message, array $context = array())
    {
        if (!$this->config->canLog()) {
            return;
        }

        $levels = array(
            LogLevel::DEBUG => Zend_log::DEBUG,
            LogLevel::INFO => Zend_log::INFO,
            LogLevel::NOTICE => Zend_log::NOTICE,
            LogLevel::WARNING => Zend_log::WARN,
            LogLevel::ERROR => Zend_log::ERR,
            LogLevel::ALERT => Zend_log::ALERT,
            LogLevel::CRITICAL => Zend_log::CRIT,
            LogLevel::EMERGENCY => Zend_log::EMERG,
        );

        Mage::log($message, $levels[$level], 'alma.log');
    }
}
