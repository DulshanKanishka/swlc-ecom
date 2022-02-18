<?php
namespace Magenest\Xero\Helper\Signature;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\Dir\Reader as ReaderDir;
use Magento\Framework\Module\Dir;

/**
 * Class Rsa
 * @package Magenest\Xero\Helper\Signature
 */
class Rsa
{
    /**
     * @const
     */
    const PUBLIC_KEY_PATH = 'magenest_xero_config/xero_api/public_key';
    const PRIVATE_KEY_PATH = 'magenest_xero_config/xero_api/private_key';

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * @var ReaderDir
     */
    protected $reader;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Rsa constructor.
     * @param ReadFactory $readFactory
     * @param ReaderDir $reader
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ReadFactory $readFactory,
        ReaderDir $reader,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->readFactory = $readFactory;
        $this->reader = $reader;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    protected function getPublicKey()
    {
        $value = trim($this->scopeConfig->getValue(self::PUBLIC_KEY_PATH));
        return preg_replace('/\t+/', '', $value);

    }

    /**
     * @return string
     */
    private function getPrivateKey()
    {
        $value = trim($this->scopeConfig->getValue(self::PRIVATE_KEY_PATH));
        return preg_replace('/\t+/', '', $value);

    }


    /**
     * @param $pathFile
     * @return string
     */
    private function readFile($pathFile)
    {
        $moduleEtcPath = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, 'Magenest_Xero');
        $configFilePath = $moduleEtcPath . $pathFile;
        $directoryRead = $this->readFactory->create($moduleEtcPath);
        $configFilePath = $directoryRead->getRelativePath($configFilePath);
        $fileContent = $directoryRead->readFile($configFilePath);

        return $fileContent;
    }


    /**
     * @param string $sign
     * @return string
     * @throws \Exception
     */
    public function signature($sign = '')
    {
        $publicKey = openssl_get_publickey($this->getPublicKey());
        if (!$publicKey) {
            throw new \Exception('Cannot access public key for signing');
        }

        $privateKey = openssl_pkey_get_private($this->getPrivateKey());
        if ($privateKey) {
            openssl_sign($sign, $signEncode, $privateKey);
            openssl_free_key($privateKey);

            return base64_encode($signEncode);
        } else {
            throw new \Exception('Cannot access private key for signing');
        }
    }
}
