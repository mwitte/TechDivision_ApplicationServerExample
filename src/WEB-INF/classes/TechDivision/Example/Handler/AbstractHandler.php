<?php

/**
 * TechDivision\Example\Handler\AbstractHandler
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\Example\Handler;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use TechDivision\PersistenceContainerClient\Context\Connection\Factory;
use TechDivision\Example\Entities\Sample;

/**
 *
 * @package TechDivision\Example
 * @copyright Copyright (c) 2010 <info@techdivision.com> - TechDivision GmbH
 * @license http://opensource.org/licenses/osl-3.0.php
 *          Open Software License (OSL 3.0)
 * @author Tim Wagner <tw@techdivision.com>
 */
abstract class AbstractHandler implements MessageComponentInterface
{

    /**
     * Default request parameter containing the action to be invoked.
     *
     * @var string
     */
    const METHOD_NAME_PARAM = 'action';

    /**
     * The connected web socket clients.
     *
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * The persistence container connection to use.
     *
     * @var \TechDivision\PersistenceContainerClient\Interfaces\Connnection
     */
    protected $connection;

    /**
     * The persistence container session to use.
     *
     * @var \TechDivision\PersistenceContainerClient\Interfaces\Session
     */
    protected $session;

    /**
     * Initializes the message handler with the container.
     *
     * @return void
     */
    public function __construct()
    {
        // initialize the object storage for the client connections
        $this->clients = new \SplObjectStorage();

        // create proxy connnection + session
        $this->connection = Factory::createContextConnection();
        $this->session = $this->connection->createContextSession();
    }

    /**
     * Creates a new proxy for the passed session bean class name
     * and returns it.
     *
     * @param string $proxyClass
     *            The session bean class name to return the proxy for
     * @return mixed The proxy instance
     */
    public function getProxy($proxyClass)
    {
        $initialContext = $this->session->createInitialContext();
        return $initialContext->lookup($proxyClass);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Ratchet\ComponentInterface::onOpen()
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn, 0);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Ratchet\ComponentInterface::onClose()
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Ratchet\MessageInterface::onMessage()
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // initialize request params and method name
        $params = array();
        parse_str($msg, $params);
        $methodName = $this->getMethodName($params);
        // prepare params and action and invoke it
        $reflectionObject = new \ReflectionObject($this);
        if ($reflectionObject->hasMethod($methodName)) {
            $reflectionMethod = $reflectionObject->getMethod($methodName);
            $result = $reflectionMethod->invokeArgs($this, $this->prepareParams($reflectionMethod, $params));
        }
        // send JSON encoded answer back to clients
        foreach ($this->clients as $client) {
            $client->send(json_encode($result));
        }
    }

    /**
     * Sorts the request params to match the action methods params
     * and strips the action param.
     *
     * @param \ReflectionMethod $reflectionMethod
     *            The reflection method to prepare the params for
     * @param array $params
     *            The params to prepare
     * @return array The request params prepared for the reflection method
     */
    protected function prepareParams(\ReflectionMethod $reflectionMethod, array $params)
    {
        $preparedParams = array();
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $preparedParams[$reflectionParameter->getPosition()] = $params[$reflectionParameter->getName()];
        }
        return $preparedParams;
    }

    /**
     * Returns the prepared action method name and returns it.
     *
     * @param array $params
     *            The request params to prepare the action method from
     * @throws \Exception Is thrown if the param containing the action method name to invoke is missing
     * @return string The prepared action method name
     */
    protected function getMethodName(array $params)
    {
        if (array_key_exists(self::METHOD_NAME_PARAM, $params)) {
            return $params[self::METHOD_NAME_PARAM] . ucfirst(self::METHOD_NAME_PARAM);
        }
        throw new \Exception('Missing action parameter in request');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Ratchet\ComponentInterface::onError()
     */
    public function onError(ConnectionInterface $conn,\Exception $e)
    {
        error_log($e->__toString());
        $conn->close();
    }
}