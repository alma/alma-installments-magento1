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

if (class_exists('Alma\API\Client', false) == false) {
    require_once Mage::getBaseDir('lib') . '/Alma_Installments/autoload.php';
}

use Alma\API\Client;

class Alma_Installments_Helper_AlmaClient extends Mage_Core_Helper_Abstract
{
    /** @var Alma_Installments_Helper_Config */
    private $config;

    /** @var \Alma\API\Client */
    private $alma;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->config = Mage::helper('alma/config');
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->alma = null;
    }

    public function getDefaultClient()
    {
        if ($this->alma === null) {
            $this->alma = $this->createInstance($this->config->getActiveAPIKey(), $this->config->getActiveMode());
        }

        return $this->alma;
    }

    public function createInstance($apiKey, $mode)
    {
        $alma = null;

        try {
            $alma = new Client($apiKey, array('mode' => $mode, 'logger' => $this->logger));

            $alma->addUserAgentComponent('Magento', Mage::getVersion());
            $alma->addUserAgentComponent('Alma for Magento 1', $this->getExtensionVersion());
        } catch (\Exception $e) {
            $this->logger->error("Error creating Alma API client {$e->getMessage()}");
        }

        return $alma;
    }

    private function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Alma_Installments->version;
    }
}
