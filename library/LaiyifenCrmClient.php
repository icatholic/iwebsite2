<?php
/**
 * 
 * @author ming
 *
 */

class LaiyifenCrmClient
{

    /**
     * 交易客户端编码
     *
     * @var string
     */
    private $_clientCode = 'JNFX0001';

    /**
     * 交易服务查询服务地址
     *
     * @var string
     */
    private $_serviceWdsl = 'http://10.1.0.178/HisComSvr/HsCRMWebSrv.dll/wsdl/IHsCRMWebSrv';

    /**
     * soap 客户端
     *
     * @var object
     */
    private $_soapClient;

    /**
     * 工作密钥
     *
     * @var string
     */
    private $_workKey;

    /**
     * 构建函数
     *
     * @param string $options            
     */
    public function __construct($options = null)
    {
        $this->setOptions($options);
    }

    /**
     * 创建一个Soap Client
     *
     * @return SoapClient boolean
     */
    private function createSoapClient()
    {
        try {
            $options = array(
                'soap_version' => SOAP_1_2, // 必须是1.2版本的soap协议，支持soapheader
                'exceptions' => true,
                'trace' => true,
                'connection_timeout' => 30, // 避免网络延迟导致的链接丢失
                'keep_alive' => true,
                'compression' => true
            );
            
            ini_set('default_socket_timeout', 30);
            $this->_soapClient = new SoapClient($this->_serviceWdsl, $options);
            return $this->_soapClient;
        } catch (SoapFault $e) {
            return false;
        }
    }

    /**
     * 设定参数
     *
     * @param array $options            
     */
    public function setOptions($options)
    {
        if (! empty($options)) {
            $this->clientCode = $options['$clientCode'];
        }
    }

    /**
     * 获取参数
     */
    public function getOptions()
    {}

    /**
     * 生成随机数
     *
     * @return number
     */
    private function random()
    {
        return abs(crc32(uniqid()));
    }

    /**
     * 获取公共密钥
     */
    public function getSystemPulicKey()
    {
        $datas = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <InputParameter>
            <Random>" . $this->random() . "</ Random >
            <ClientCode>" . $this->clientCode . "</ClientCode>
            </InputParameter>";
    }

    /**
     * 产生工作密钥
     *
     * @return string
     */
    private function workKey()
    {
        $this->_workKey = substr(md5(uniqid()), 0, 16);
        return $this->_workKey;
    }

    /**
     * 用户签入
     *
     * @return string
     */
    public function clientSignIn()
    {
        $datas = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <InputParameter>
        <Random>" . $this->random() . "</Random>
        <ClientCode>" . $this->_clientCode . "</ClientCode> 
        <WorkKey>" . $this->workKey() . "</WorkKey>
        <UserCode>8899</UserCode>
        <Passwd>1234</Passwd>
        <VerifyInfo></VerifyInfo>
        <Computer>计算机名称</Computer>
        <TerminalNo>终端标志号</TerminalNo>
        </InputParameter>";
    }

    /**
     * 3DES加密,兼容.net模式
     */
    private function crypt3desEncode($text, $key)
    {
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        $key_add = 24 - strlen($key);
        $key .= substr($key, 0, $key_add);
        $text_add = strlen($text) % 8;
        for ($i = $text_add; $i < 8; $i ++) {
            $text .= chr(8 - $text_add);
        }
        $vector = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $vector);
        $encrypt64 = mcrypt_generic($td, $text);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $encrypt64;
    }

    /**
     * 3DES加密
     */
    private function crypt3desDecode($string, $key)
    {
        return $string;
    }

    /**
     * 使用公钥对数据进行RSA加密
     * @param string $string
     * @param string $publicKey
     * @return string
     */
    private function rsaEncode($string, $publicKey)
    {
        openssl_public_encrypt($string, $encrypted, $publicKey);
        return $encrypted;
    }

    /**
     * RSA public key $modulus $exponent转化为asn.1格式
     *
     * @param string $modulus            
     * @param string $exponent            
     * @return string
     */
    private function publicKeyPair2Asn1($modulus, $exponent)
    {
        return Zeal_Security_RSAPublicKey::getPublicKeyFromModExp($modulus, $exponent);
    }
}

class Zeal_Security_RSAPublicKey
{

    /**
     * ASN.1 type INTEGER class
     */
    const ASN_TYPE_INTEGER = 0x02;

    /**
     * ASN.1 type BIT STRING class
     */
    const ASN_TYPE_BITSTRING = 0x03;

    /**
     * ASN.1 type SEQUENCE class
     */
    const ASN_TYPE_SEQUENCE = 0x30;

    /**
     * The Identifier for RSA Keys
     */
    const RSA_KEY_IDENTIFIER = '300D06092A864886F70D0101010500';

    /**
     * Constructor (disabled)
     *
     * @return void
     */
    private function __construct()
    {}

    /**
     * Transform an RSA Key in x.509 string format into a PEM encoding and
     * return an PEM encoded string for openssl to handle
     *
     * @param string $certificate
     *            x.509 format cert string
     * @return string The PEM encoded version of the key
     */
    static public function getPublicKeyFromX509($certificate)
    {
        $publicKeyString = "-----BEGIN CERTIFICATE-----n" . wordwrap($certificate, 64, "n", true) . "n-----END CERTIFICATE-----";
        
        return $publicKeyString;
    }

    /**
     * Transform an RSA Key in Modulus/Exponent format into a PEM encoding and
     * return an PEM encoded string for openssl to handle
     *
     * @param string $modulus
     *            The RSA Modulus in binary format
     * @param string $exponent
     *            The RSA exponent in binary format
     * @return string The PEM encoded version of the key
     */
    static public function getPublicKeyFromModExp($modulus, $exponent)
    {
        $modulusInteger = self::_encodeValue($modulus, self::ASN_TYPE_INTEGER);
        $exponentInteger = self::_encodeValue($exponent, self::ASN_TYPE_INTEGER);
        $modExpSequence = self::_encodeValue($modulusInteger . $exponentInteger, self::ASN_TYPE_SEQUENCE);
        $modExpBitString = self::_encodeValue($modExpSequence, self::ASN_TYPE_BITSTRING);
        
        $binRsaKeyIdentifier = pack("H*", self::RSA_KEY_IDENTIFIER);
        
        $publicKeySequence = self::_encodeValue($binRsaKeyIdentifier . $modExpBitString, self::ASN_TYPE_SEQUENCE);
        
        $publicKeyInfoBase64 = base64_encode($publicKeySequence);
        
        $publicKeyString = "-----BEGIN PUBLIC KEY-----n";
        $publicKeyString .= wordwrap($publicKeyInfoBase64, 64, "n", true);
        $publicKeyString .= "n-----END PUBLIC KEY-----n";
        
        return $publicKeyString;
    }

    /**
     * Encode a limited set of data types into ASN.1 encoding format
     * which is used in X.509 certificates
     *
     * @param string $data
     *            The data to encode
     * @param const $type
     *            The encoding format constant
     * @return string The encoded value
     * @throws Zend_InfoCard_Xml_Security_Exception
     */
    static protected function _encodeValue($data, $type)
    {
        // Null pad some data when we get it
        // (integer values > 128 and bitstrings)
        if ((($type == self::ASN_TYPE_INTEGER) && (ord($data) > 0x7f)) || ($type == self::ASN_TYPE_BITSTRING)) {
            $data = "\0$data";
        }
        
        $len = strlen($data);
        
        // encode the value based on length of the string
        switch (true) {
            case ($len < 128):
                return sprintf("%c%c%s", $type, $len, $data);
            case ($len < 0x0100):
                return sprintf("%c%c%c%s", $type, 0x81, $len, $data);
            case ($len < 0x010000):
                return sprintf("%c%c%c%c%s", $type, 0x82, $len / 0x0100, $len % 0x0100, $data);
            default:
                throw new Zeal_Security_RSAPublicKey_Exception("Could not encode value", 1);
        }
        
        throw new Zeal_Security_RSAPublicKey_Exception("Invalid code path", 2);
    }
}

class Zeal_Security_RSAPublicKey_Exception extends Exception
{
}