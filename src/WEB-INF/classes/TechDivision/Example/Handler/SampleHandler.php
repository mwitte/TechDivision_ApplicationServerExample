<?php

/**
 * TechDivision\Example\Handler\SampleHandler
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
 * @package     TechDivision\Example
 * @copyright  	Copyright (c) 2010 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Tim Wagner <tw@techdivision.com>
 */
class SampleHandler extends AbstractHandler
{

    /**
     * Class name of the persistence container proxy that handles the data.
     * @var string
     */
    const PROXY_CLASS = 'TechDivision\Example\Services\SampleProcessor';

    /**
     * Persists the sample entity with the passed data.
     *
     * @param string $sampleId The ID to be persisted
     * @param string $name The name to be persisted
     * @return \TechDivision\Example\Entities\Sample The persisted entity
     */
    public function persistAction($sampleId, $name)
    {
        // create a new entity and persist it
        $entity = new Sample();
        $entity->setSampleId((integer) $sampleId);
        $entity->setName($name);

        // store and return the entity
        return array($this->getProxy(self::PROXY_CLASS)->persist($entity));
    }

    /**
     * Returns all sample entities.
     *
     * @return array<\TechDivision\Example\Entities\Sample> The array with the sample entities
     */
    public function overviewAction()
    {
        return $this->getProxy(self::PROXY_CLASS)->findAll();
    }
}