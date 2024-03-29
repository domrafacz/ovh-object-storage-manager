<?php

namespace SwiftManager\Models;

use Generator;
use GuzzleHttp\Psr7\Stream;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\ObjectStore\v1\Service;
use OpenStack\OpenStack;
use OpenStack\ObjectStore\v1\Models\Account;
use Slim\Collection;

class Manager
{
    private ?Collection $settings = null;
    private ?OpenStack $openstack = null;
    private ?string $region = null;
    private ?Service $objectStore = null;
    private ?Account $account = null;

    public function __construct(Collection $settings)
    {
        $this->settings = $settings;
        $this->configCheck();
    }

    private function configCheck() : void
    {
        if (empty($this->settings['authUrl'])) {
            die('config: authUrl not set!');
        }

        if (empty($this->settings['username'])) {
            die('config: username not set!');
        }

        if (empty($this->settings['password'])) {
            die('config: password not set!');
        }

        if (empty($this->settings['tenantId'])) {
            die('config: tenantId not set!');
        }

        if (empty($this->settings['tenantName'])) {
            die('config: tenantName not set!');
        }

        if (empty($this->settings['tempUrlExpireTime'])) {
            $this->settings['tempUrlExpireTime'] = 500;
        }
    }

    public function connect(string $region) : void
    {
        $this->openstack = new OpenStack([
            'authUrl' => $this->settings['authUrl'],
            'region'  => $region,
            'user'    => [
                'name' => $this->settings['username'],
                'domain'   => [
                    'id' => 'default'
                ],
                'password' => $this->settings['password'],
            ],
            'scope' => [
                'project' => [
                    'name' => $this->settings['tenantName'],
                    'domain'   => [
                        'id' => 'default'
                    ]
                ],
            ]
        ]);

        $this->objectStore = $this->openstack->objectStoreV1();
        $this->account = $this->objectStore->getAccount();
        $this->account->retrieve();
        $this->region = $region;
    }

    public function getRegions(): array
    {
        return $this->settings['regions'];
    }

    public function createContainer(string $containerName, string $containerType): Container
    {
        $params = ['name' => $containerName];

        if ($containerType == 'public') {
            $params['readAccess'] = '.r:*,.rlistings';
        } else {
            $params['readAccess'] = '';
        }

        return $this->objectStore->createContainer($params);
    }

    public function modifyContainer(array $params): Container
    {
        $containerData = ['name' => $params['container_name2']];
        $metadata = [];

        if ($params['container_type2'] == 'public') {
            $containerData['readAccess'] = '.r:*,.rlistings';
        } else {
            $containerData['readAccess'] = '';
        }

        foreach ($params as $key => $value) {
            if (strpos($key, 'meta_key_') !== false) {
                $metadata[$value] = $params['meta_value_' . substr($key, -1)];
            }
        }
        $container = $this->objectStore->createContainer($containerData);
        $container->resetMetadata($metadata);

        return $container;
    }

    public function deleteContainer(string $containerName) : void
    {
        $this->objectStore->getContainer($containerName)->delete();
    }

    public function getContainer(string $containerName): Container
    {
        try {
            $container = $this->objectStore->getContainer($containerName);
            $container->retrieve();
        } catch (\Exception $e) {
            die("Container: {$containerName} doest not exists!");
        };

        return $container;
    }

    public function getContainerUrl(string $containerName): string
    {
        return "https://storage." . strtolower($this->region) . ".cloud.ovh.net/v1/AUTH_" . $this->settings['tenantId'] . "/" . $containerName;
    }

    public function getContainerType(string $read): string
    {
        //check if read acl is present
        if (strpos($read, '.r:*') !== false) {
            return 'public';
        } else {
            return 'private';
        }
    }

    public function getContainerCount(): int
    {
        return $this->account->containerCount;
    }

    public function getObjectCount(): int
    {
        return $this->account->objectCount;
    }

    public function getBytesUsed(): int
    {
        return $this->account->bytesUsed;
    }

    public function getContainersList(): Generator
    {
        return $this->objectStore->listContainers();
    }

    public function getAccountTempUrlKey(): ?string
    {
        $key = $this->account->getMetadata()['Temp-Url-Key'];

        if (empty($key)) {
            return null;
        } else {
            return $key;
        }
    }

    public function setAccountTempUrlKey(string $key): void
    {
        $this->account->mergeMetadata([
            'Temp-Url-Key' => $key,
        ]);
    }

    public function getContainerObjectsList(string $containerName): Generator
    {
        return $this->objectStore->getContainer($containerName)->listObjects();
    }

    public function getObjectTempUrl(string $containerName, string $objectName, int $expires): string
    {
        $object = $this->objectStore->getContainer($containerName)->getObject($objectName);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            die('object does not exists');
        };

        $objectUrl = $this->objectStore->getContainer($containerName)->getObject($objectName)->getPublicUri();
        $key = $this->account->getMetadata()['Temp-Url-Key'];

        if (empty($key)) {
            die('X-Account-Meta-Temp-URL-Key not set!');
        }

        $method = 'GET';
        $path = "/v1/AUTH_" . $this->settings['tenantId'] . "/{$containerName}/{$objectName}";

        $key = $this->account->getMetadata()['Temp-Url-Key'];

        if ($expires == 0) {
            $expires = time() + $this->settings['tempUrlExpireTime'];
        }

        $hmac_body = "$method\n$expires\n$path";
        $sig = hash_hmac("sha1", $hmac_body, $key);
        $objectUrl .= "?temp_url_sig=$sig&temp_url_expires=$expires";

        return $objectUrl;
    }

    public function setCors(string $containerName, string $cors): void
    {
        $this->objectStore->getContainer($containerName)->mergeMetadata([
            'Access-Control-Expose-Headers' => $cors,
            'Access-Control-Allow-Origin' => $cors,
        ]);
    }

    public function createObject(string $containerName, string $path, string $fileName): StorageObject
    {
        $stream = new Stream(fopen($path . $fileName, 'r'));

        $options = [
            'name' => $fileName,
            'stream' => $stream,
        ];

        return $this->objectStore->getContainer($containerName)->createObject($options);
    }

    public function modifyObject(string $containerName, array $params): StorageObject
    {
        $metadata = [];

        foreach ($params as $key => $value) {
            if (strpos($key, 'meta_key_') !== false) {
                $metadata[$value] = $params['meta_value_' . substr($key, -1)];
            }
        }

        $object = $this->objectStore->getContainer($containerName)->getObject($params['modify_object_name']);
        $object->resetMetadata($metadata);

        return $object;
    }

    public function deleteObject(string $containerName, string $objectName): void
    {
        $this->objectStore->getContainer($containerName)->getObject($objectName)->delete();
    }

    public function deleteAllObjects(string $containerName): void
    {
        $container = $this->objectStore->getContainer($containerName);
        $objectList = $this->objectStore->getContainer($containerName)->listObjects();

        foreach ($objectList as $object) {
            $container->getObject($object->name)->delete();
        }
    }

    public function formatFilesize(int $filesize): string
    {
        $suffix = array('B', 'KB', 'MB', 'GB');
        $unit = floor(($filesize ? log($filesize) : 0) / log(1024));
        $unit = min($unit, count($suffix) - 1);
        $filesize /= pow(1024, $unit);

        return round($filesize, 2) . ' ' . $suffix[$unit];
    }

    public function urlEncode(string $url): string
    {
        return urlencode($url);
    }

    public function isAjaxUploadPossible(string $containerName): bool
    {
        $container = $this->getContainer($containerName);
        $metadata = $container->getMetadata();

        if (isset($metadata['Access-Control-Expose-Headers'], $metadata['Access-Control-Allow-Origin'])) {
            if ($metadata['Access-Control-Expose-Headers'] == '*' && $metadata['Access-Control-Allow-Origin'] == '*') {
                return true;
            }
        }

        return false;
    }
}
