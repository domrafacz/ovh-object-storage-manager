<?php

namespace SwiftManager\Controllers;

use Slim\Http\Response;
use SwiftManager\Models\Manager;
use \Slim\Container;

class MainController
{
    private Container $container;
    private Manager $manager;

    public function __construct(&$container)
    {
        $this->container = $container;
        $this->manager = new Manager($this->container->get('settings'));
    }

    public function index($request, $response, $args) : Response
    {
        $test = $this->container->get('settings');
        return $this->container->get('view')->render($response, 'index.html.twig', ['regions' => $this->manager->getRegions()]);
    }

    public function listContainers($request, $response, $args) : Response
    {
        $this->manager->connect($args['region']);

        $params = $request->getParams();

        if (isset($params['container_name'], $params['container_type'])) {
            $this->manager->createContainer($params['container_name'], $params['container_type']);
            $this->container->get('flash')->addMessageNow('success', 'Container has been created');
        }

        if (isset($params['delete_container_name'])) {
            $this->manager->deleteContainer($params['delete_container_name']);
            $this->container->get('flash')->addMessageNow('success', 'Container has been deleted');
        }

        if (isset($params['container_name2'], $params['container_type2'])) {
            $this->manager->modifyContainer($params);
            $this->container->get('flash')->addMessageNow('success', 'Container has been modified');
        }

        return $this->container->get('view')->render($response, 'container-list.html.twig', [
            'containers' => $this->manager->getContainersList(),
            'region' => $args['region'],
            'container_count' => $this->manager->getContainerCount(),
            'object_count' => $this->manager->getObjectCount(),
            'bytes_used' => $this->manager->getBytesUsed(),
            'manager' => $this->manager,
        ]);
    }

    public function listObjects($request, $response, $args) : Response
    {
        $this->manager->connect($args['region']);
        $params = $request->getParams();

        if (isset($params['delete_all_objects'])) {
            $this->manager->deleteAllObjects($args['container']);
            $this->container->get('flash')->addMessageNow('success', 'All objects has been deleted');
        }

        if (isset($params['delete_object_name'])) {
            $this->manager->deleteObject($args['container'], $params['delete_object_name']);
            $this->container->get('flash')->addMessageNow('success', 'Object has been deleted');
        }

        if (isset($params['modify_object_name'])) {
            $this->manager->modifyObject($args['container'], $params);
            $this->container->get('flash')->addMessageNow('success', 'Object has been modified');
        }

        $containerUrl = $this->manager->getContainerUrl($args['container']);
        $maxFileSize = 5368709120;
        $maxFileCount = 10;
        $expires = time() + 3600;
        $redirect = "http://{$_SERVER['SERVER_NAME']}/upload-object-success";
        $path = "/v1/AUTH_" . $this->container->get('settings')['tenantId'] . "/{$args['container']}";
        $body = sprintf("%s\n%s\n%s\n%s\n%s", $path, $redirect, $maxFileSize, $maxFileCount, $expires);
        $signature = hash_hmac('sha1', $body, $this->manager->getAccountTempUrlKey());

        return $this->container->get('view')->render($response, 'objects-list.html.twig', [
            'objects' => $this->manager->getContainerObjectsList($args['container']),
            'region' => $args['region'],
            'object_count' => $this->manager->getObjectCount(),
            'container' => $this->manager->getContainer($args['container']),
            'container_url' => $containerUrl,
            'max_file_size' => $maxFileSize,
            'max_file_count' => $maxFileCount,
            'redirect' => $redirect,
            'signature' => $signature,
            'expires' => $expires,
            'manager' => $this->manager,
            'ajax_upload' => $this->manager->isAjaxUploadPossible($args['container']),
        ]);
    }

    public function allowCors($request, $response, $args) : Response
    {
        $this->manager->connect($args['region']);
        $this->manager->setCors($args['container'], '*');
        return $response->withRedirect("/list-objects/{$args['region']}/{$args['container']}");
    }

    public function tempUrl($request, $response, $args) : Response
    {
        $this->manager->connect($args['region']);

        if (!isset($args['expires'])) {
            $args['expires'] = 0;
        }

        return $response->withRedirect($this->manager->getObjectTempUrl($args['container'], $args['object'], $args['expires']));
    }

    public function tempUrlSettings($request, $response, $args) : Response
    {
        $this->manager->connect($args['region']);

        $params = $request->getParams();

        if (isset($params['temp_url_key'])) {
            $this->manager->setAccountTempUrlKey($params['temp_url_key']);
            $this->container->get('flash')->addMessageNow('success', 'Temp url key has been changed');
        }
        
        return $this->container->get('view')->render($response, 'temp-url-settings.html.twig', [
            'region' => $args['region'],
            'manager' => $this->manager,
        ]);
    }

    public function uploadObject($request, $response, $args) : Response
    {
        $this->manager->connect($args['region']);

        $params = $request->getParams();

        $containerUrl = $this->manager->getContainerUrl($args['container']);
        $maxFileSize = 5368709120;
        $maxFileCount = 1000;
        $expires = time() + 3600;
        $redirect = "http://{$_SERVER['SERVER_NAME']}/upload-object/{$args['region']}/{$args['container']}";
        $path = "/v1/AUTH_" . $this->container->get('settings')['tenantId'] . "/{$args['container']}";
        $body = sprintf("%s\n%s\n%s\n%s\n%s", $path, $redirect, $maxFileSize, $maxFileCount, $expires);
        $signature = hash_hmac('sha1', $body, $this->manager->getAccountTempUrlKey());

        $container = $this->manager->getContainer($args['container']);

        $viewData = [
            'region' => $args['region'],
            'container' => $container,
            'container_url' => $containerUrl,
            'max_file_size' => $maxFileSize,
            'max_file_count' => $maxFileCount,
            'redirect' => $redirect,
            'signature' => $signature,
            'expires' => $expires,
            'manager' => $this->manager,
        ];

        if (!empty($params['message'])) {
            $viewData['return_message'] = $params['message'];
        }

        if (!empty($params['status'])) {
            $viewData['return_status'] = $params['status'];
        }

        return $this->container->get('view')->render($response, 'upload-object.html.twig', $viewData);
    }

    public function uploadObjectSuccess($request, $response, $args) : Response
    {
        header('Access-Control-Allow-Origin: *');
        return $response->write('success');
    }
}
