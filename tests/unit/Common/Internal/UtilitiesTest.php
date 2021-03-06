<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Tests\Unit\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Tests\unit\Common\Internal;

use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Tests\Framework\TestResources;
use MicrosoftAzure\Storage\Tests\Framework\VirtualFileSystem;
use MicrosoftAzure\Storage\Common\Models\ServiceProperties;
use MicrosoftAzure\Storage\Common\Internal\Serialization\XmlSerializer;
use GuzzleHttp\Psr7;

/**
 * Unit tests for class Utilities
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Tests\Unit\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class UtilitiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryGetValue
     */
    public function testTryGetValue()
    {
        // Setup
        $key = 0;
        $expected = 10;
        $data = array(10, 20, 30);

        // Test
        $actual = Utilities::tryGetValue($data, $key);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryGetValue
     */
    public function testTryGetValueUsingDefault()
    {
        // Setup
        $key = 10;
        $expected = 6;
        $data = array(10, 20, 30);

        // Test
        $actual = Utilities::tryGetValue($data, $key, $expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryGetValue
     */
    public function testTryGetValueWithNull()
    {
        // Setup
        $key = 10;
        $data = array(10, 20, 30);

        // Test
        $actual = Utilities::tryGetValue($data, $key);

        $this->assertNull($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryGetKeysChainValue
     */
    public function testTryGetKeysChainValue()
    {
        // Setup
        $array = array();
        $array['a1'] = array();
        $array['a2'] = 'value1';
        $array['a1']['b1'] = array();
        $array['a1']['b2'] = 'value2';
        $array['a1']['b1']['c1'] = 'value3';

        // Test - Level 1
        $this->assertEquals('value1', Utilities::tryGetKeysChainValue($array, 'a2'));
        $this->assertEquals(null, Utilities::tryGetKeysChainValue($array, 'a3'));

        // Test - Level 2
        $this->assertEquals('value2', Utilities::tryGetKeysChainValue($array, 'a1', 'b2'));
        $this->assertEquals(null, Utilities::tryGetKeysChainValue($array, 'a1', 'b3'));

        // Test - Level 3
        $this->assertEquals('value3', Utilities::tryGetKeysChainValue($array, 'a1', 'b1', 'c1'));
        $this->assertEquals(null, Utilities::tryGetKeysChainValue($array, 'a1', 'b1', 'c2'));
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::startsWith
     */
    public function testStartsWith()
    {
        // Setup
        $string = 'myname';
        $prefix = 'my';

        // Test
        $actual = Utilities::startsWith($string, $prefix);

        $this->assertTrue($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::startsWith
     */
    public function testStartsWithDoesNotStartWithPrefix()
    {
        // Setup
        $string = 'amyname';
        $prefix = 'my';

        // Test
        $actual = Utilities::startsWith($string, $prefix);

        $this->assertFalse($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getArray
     */
    public function testGetArray()
    {
        // Setup
        $expected = array(array(1, 2, 3, 4),  array(5, 6, 7, 8));

        // Test
        $actual = Utilities::getArray($expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getArray
     */
    public function testGetArrayWithFlatValue()
    {
        // Setup
        $flat = array(1, 2, 3, 4, 5, 6, 7, 8);
        $expected = array(array(1, 2, 3, 4, 5, 6, 7, 8));

        // Test
        $actual = Utilities::getArray($flat);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getArray
     */
    public function testGetArrayWithMixtureValue()
    {
        // Setup
        $flat = array(array(10, 2), 1, 2, 3, 4, 5, 6, 7, 8);
        $expected = array(array(array(10, 2), 1, 2, 3, 4, 5, 6, 7, 8));

        // Test
        $actual = Utilities::getArray($flat);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getArray
     */
    public function testGetArrayWithEmptyValue()
    {
        // Setup
        $empty = array();
        $expected = array();

        // Test
        $actual = Utilities::getArray($empty);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::unserialize
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::_sxml2arr
     */
    public function testUnserialize()
    {
        // Setup
        $propertiesSample = TestResources::getServicePropertiesSample();
        $properties = ServiceProperties::create($propertiesSample);
        $xmlSerializer = new XmlSerializer();
        $xml = $properties->toXml($xmlSerializer);
        $expected = $properties->toArray();

        // Test
        $actual = Utilities::unserialize($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::serialize
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::_arr2xml
     */
    public function testSerialize()
    {
        // Setup
        $propertiesSample = TestResources::getServicePropertiesSample();
        $properties = ServiceProperties::create($propertiesSample);
        $expected  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $expected .= '<StorageServiceProperties><Logging><Version>1.0</Version><Delete>true</Delete>';
        $expected .= '<Read>false</Read><Write>true</Write><RetentionPolicy><Enabled>true</Enabled>';
        $expected .= '<Days>20</Days></RetentionPolicy></Logging><HourMetrics><Version>1.0</Version>';
        $expected .= '<Enabled>true</Enabled><IncludeAPIs>false</IncludeAPIs><RetentionPolicy>';
        $expected .= '<Enabled>true</Enabled><Days>20</Days></RetentionPolicy></HourMetrics></StorageServiceProperties>';
        $array = $properties->toArray();

        // Test
        $actual = Utilities::serialize($array, ServiceProperties::$xmlRootName);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::serialize
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::_arr2xml
     */
    public function testSerializeAttribute()
    {
        // Setup
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Object field1="value1" field2="value2"/>';

        $object = array(
            '@attributes' => array(
                'field1' => 'value1',
                'field2' => 'value2'
            )
        );

        // Test
        $actual = Utilities::serialize($object, 'Object');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::toBoolean
     */
    public function testToBoolean()
    {
        // Setup
        $value = 'true';
        $expected = true;

        // Test
        $actual = Utilities::toBoolean($value);

        // Assert
        $this->assertTrue(is_bool($actual));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::booleanToString
     */
    public function testBooleanToString()
    {
        // Setup
        $expected = 'true';
        $value = true;

        // Test
        $actual = Utilities::booleanToString($value);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::isoDate
     */
    public function testIsoDate()
    {
        // Test
        $date = Utilities::isoDate();

        // Assert
        $this->assertNotNull($date);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::convertToEdmDateTime
     */
    public function testConvertToEdmDateTime()
    {
        // Test
        $actual = Utilities::convertToEdmDateTime(new \DateTime());

        // Assert
        $this->assertNotNull($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::convertToDateTime
     */
    public function testConvertToDateTime()
    {
        // Setup
        $date = '2008-10-01T15:26:13Z';

        // Test
        $actual = Utilities::convertToDateTime($date);

        // Assert
        $this->assertInstanceOf('\DateTime', $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::convertToDateTime
     */
    public function testConvertToDateTimeWithDate()
    {
        // Setup
        $date = new \DateTime();

        // Test
        $actual = Utilities::convertToDateTime($date);

        // Assert
        $this->assertEquals($date, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::stringToStream
     */
    public function testStringToStream()
    {
        $data = 'This is string';
        $expected = fopen('data://text/plain,' . $data, 'r');

        // Test
        $actual = Utilities::stringToStream($data);

        // Assert
        $this->assertEquals(stream_get_contents($expected), stream_get_contents($actual));
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::rfc1123ToDateTime
     */
    public function testWindowsAzureDateToDateTime()
    {
        // Setup
        $expected = 'Fri, 16 Oct 2009 21:04:30 GMT';

        // Test
        $actual = Utilities::rfc1123ToDateTime($expected);

        // Assert
        $this->assertEquals($expected, $actual->format('D, d M Y H:i:s T'));
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryAddUrlScheme
     */
    public function testTryAddUrlSchemeWithScheme()
    {
        // Setup
        $url = 'http://microsoft.com';

        // Test
        $actual = Utilities::tryAddUrlScheme($url);

        // Assert
        $this->assertEquals($url, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryAddUrlScheme
     */
    public function testTryAddUrlSchemeWithoutScheme()
    {
        // Setup
        $url = 'microsoft.com';
        $expected = 'http://microsoft.com';

        // Test
        $actual = Utilities::tryAddUrlScheme($url);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::startsWith
     */
    public function testStartsWithIgnoreCase()
    {
        // Setup
        $string = 'MYString';
        $prefix = 'mY';

        // Test
        $actual = Utilities::startsWith($string, $prefix, true);

        // Assert
        $this->assertTrue($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::inArrayInsensitive
     */
    public function testInArrayInsensitive()
    {
        // Setup
        $value = 'CaseInsensitiVe';
        $array = array('caSeinSenSitivE');

        // Test
        $actual = Utilities::inArrayInsensitive($value, $array);

        // Assert
        $this->assertTrue($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::inArrayInsensitive
     */
    public function testArrayKeyExistsInsensitive()
    {
        // Setup
        $key = 'CaseInsensitiVe';
        $array = array('caSeinSenSitivE' => '123');

        // Test
        $actual = Utilities::arrayKeyExistsInsensitive($key, $array);

        // Assert
        $this->assertTrue($actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::tryGetValueInsensitive
     */
    public function testTryGetValueInsensitive()
    {
        // Setup
        $key = 'KEy';
        $value = 1;
        $array = array($key => $value);

        // Test
        $actual = Utilities::tryGetValueInsensitive('keY', $array);

        // Assert
        $this->assertEquals($value, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getGuid
     */
    public function testGetGuid()
    {
        // Test
        $actual1 = Utilities::getGuid();
        $actual2 = Utilities::getGuid();

        // Assert
        $this->assertNotNull($actual1);
        $this->assertNotNull($actual2);
        $this->assertInternalType('string', $actual1);
        $this->assertInternalType('string', $actual2);
        $this->assertNotEquals($actual1, $actual2);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::endsWith
     */
    public function testEndsWith()
    {
        // Setup
        $haystack = 'tesT';
        $needle = 't';
        $expected = true;

        // Test
        $actual = Utilities::endsWith($haystack, $needle, true);

        // Assert
        $this->assertEquals($expected, $actual);
    }

//     /**
//      * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getEntityId
//      */
//     public function testGetEntityIdWithString(){

//         // Setup
//         $id = 'kjgdfg57';

//         // Test
//         $result = Utilities::GetEntityId($id, 'MicrosoftAzure\Storage\MediaServices\Models\Asset');

//         //Assert
//         $this->assertEquals($id, $result);
//     }

//     /**
//      * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getEntityId
//      */
//     public function testGetEntityIdWithObject(){

//         // Setup
//         $idKey = 'Id';
//         $optionKey = 'Options';
//         $assetArray= array(
//                 $idKey                  => 'kjgdfg57',
//                 $optionKey             => Asset::OPTIONS_NONE,
//         );
//         $value = Asset::createFromOptions($assetArray);

//         // Test
//         $result = Utilities::GetEntityId($value,'MicrosoftAzure\Storage\MediaServices\Models\Asset');

//         //Assert
//         $this->assertEquals($assetArray[$idKey], $result);
//     }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::generateCryptoKey
     */
    public function testGenerateCryptoKey()
    {

        // Setup
        $length = 32;

        // Test
        $result = Utilities::generateCryptoKey($length);

        // Assert
        $this->assertEquals($length, strlen($result));
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::ctrCrypt
     */
    public function testCtrCrypt()
    {
    
        // Setup
        $data = 'Test data more than 16 bytes';
        $key = Utilities::generateCryptoKey(32);
        $efectiveInitializationVector = Utilities::generateCryptoKey(8);
        $initializationVector = str_pad($efectiveInitializationVector, 16, chr(255));
    
        // Test
        $ecnrypted = Utilities::ctrCrypt($data, $key, $initializationVector);
        $decrypted = Utilities::ctrCrypt($ecnrypted, $key, $initializationVector);
    
        // Assert
        $this->assertEquals($data, $decrypted);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::ctrCrypt
     */
    public function testCtrCryptFixedKeys()
    {
    
        // Setup
        $data = 'Test data more than 16 bytes';
        $key = base64_decode('QNhZJajWRH3fmCKDJtMluj6PUBvkADwJ7dX4KQGI99o=');
        $efectiveInitializationVector = base64_decode('k3AmLEGFubw=');
        $expected = base64_decode('j3+9MFQVctoWlUvqbn/xReun0XnWqwJ3tpvbpw==');
        
        $initializationVector = str_pad($efectiveInitializationVector, 16, chr(255));
        
        // Test
        $actual = Utilities::ctrCrypt($data, $key, $initializationVector);
    
        // Assert
        $this->assertEquals($actual, $expected);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::ctrCrypt
     */
    public function testCtrCryptInvalidKeyLength()
    {
    
        // Setup
        $data = 'Test data more than 16 bytes';
        $key = '12345';
        $efectiveInitializationVector = Utilities::generateCryptoKey(8);
        $this->setExpectedException(get_class(new \InvalidArgumentException('')));
        
        $initializationVector = str_pad($efectiveInitializationVector, 16, chr(255));
        
        // Test
        $actual = Utilities::ctrCrypt($data, $key, $initializationVector);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::ctrCrypt
     */
    public function testCtrCryptInvalidInitializationVectorLength()
    {
    
        // Setup
        $data = 'Test data more than 16 bytes';
        $key = Utilities::generateCryptoKey(32);
        $initializationVector = '1234';
        $this->setExpectedException(get_class(new \InvalidArgumentException('')));
        
        // Test
        $actual = Utilities::ctrCrypt($data, $key, $initializationVector);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::base256ToDec
     */
    public function testBase256ToDecF()
    {
    
        // Setup
        $data = pack('C*', 255, 255, 255, 255);
        $expected = 4294967295;
    
        // Test
        $actual = Utilities::base256ToDec($data);
    
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::base256ToDec
     */
    public function testBase256ToDec0()
    {
    
        // Setup
        $data = pack('C*', 0, 0, 0, 0);
        $expected = 0;
    
        // Test
        $actual = Utilities::base256ToDec($data);
    
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::base256ToDec
     */
    public function testBase256ToDec()
    {
    
        // Setup
        $data = pack('C*', 34, 78, 27, 55);
        $expected = 575544119;
    
        // Test
        $actual = Utilities::base256ToDec($data);
    
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::base256ToDec
     */
    public function testBase256ToDecBig()
    {
    
        // Setup
        $data = pack('C*', 81, 35, 29, 39, 236, 104, 105, 144); //51 23 1D 27 EC 68 69 90
        $expected = '5846548798564231568';
    
        // Test
        $actual = Utilities::base256ToDec($data);
    
        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::isStreamLargerThanSizeOrNotSeekable
     */
    public function testIsStreamLargerThanSizeOrNotSeekable()
    {
        //prepare a file
        $cwd = getcwd();
        $uuid = uniqid('test-file-', true);
        $path = $cwd.DIRECTORY_SEPARATOR.$uuid.'.txt';
        $resource = fopen($path, 'w+');
        $count = 64 / 4;
        for ($index = 0; $index < $count; ++$index) {
            fwrite($resource, openssl_random_pseudo_bytes(4194304));
        }
        rewind($resource);
        $stream = Psr7\stream_for($resource);
        $result_0 = Utilities::isStreamLargerThanSizeOrNotSeekable(
            $stream,
            4194304 * 16 - 1
        );
        $result_1 = Utilities::isStreamLargerThanSizeOrNotSeekable(
            $stream,
            4194304 * 16
        );
        //prepare a string
        $count = 64 / 4;
        $testStr = openssl_random_pseudo_bytes(4194304 * $count);
        $stream = Psr7\stream_for($testStr);
        $result_2 = Utilities::isStreamLargerThanSizeOrNotSeekable(
            $stream,
            4194304 * 16 - 1
        );
        $result_3 = Utilities::isStreamLargerThanSizeOrNotSeekable(
            $stream,
            4194304 * 16
        );

        $this->assertFalse($result_1);
        $this->assertFalse($result_3);
        $this->assertTrue($result_0);
        $this->assertTrue($result_2);
        if (is_resource($resource)) {
            fclose($resource);
        }
        // Delete file after assertion.
        unlink($path);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getMetadataArray
     */
    public function testGetMetadataArray()
    {
        // Setup
        $expected = array('key1' => 'value1', 'myname' => 'azure', 'mycompany' => 'microsoft_');
        $metadataHeaders = array();
        foreach ($expected as $key => $value) {
            $metadataHeaders[Resources::X_MS_META_HEADER_PREFIX . strtolower($key)] = $value;
        }

        // Test
        $actual = Utilities::getMetadataArray($metadataHeaders);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\Internal\Utilities::getMetadataArray
     */
    public function testGetMetadataArrayWithMsHeaders()
    {
        // Setup
        $key = 'name';
        $validMetadataKey = Resources::X_MS_META_HEADER_PREFIX . $key;
        $value = 'correct';
        $metadataHeaders = array('x-ms-key1' => 'value1', 'myname' => 'x-ms-date',
                          $validMetadataKey => $value, 'mycompany' => 'microsoft_');

        // Test
        $actual = Utilities::getMetadataArray($metadataHeaders);

        // Assert
        $this->assertCount(1, $actual);
        $this->assertEquals($value, $actual[$key]);
    }
}
