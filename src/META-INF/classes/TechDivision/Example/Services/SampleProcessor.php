<?php

/**
 * TechDivision\Example\Services\SampleProcessor
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\Example\Services;

use TechDivision\Example\Entities\Sample;
use TechDivision\Example\Entities\User;
use TechDivision\ApplicationServer\InitialContext;
use TechDivision\PersistenceContainer\Application;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;

/**
 * A singleton session bean implementation that handles the
 * data by using Doctrine ORM.
 *
 * @package     TechDivision\Example
 * @copyright  	Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Tim Wagner <tw@techdivision.com>
 *
 * @Singleton
 */
class SampleProcessor {

    /**
     * The application instance that provides the entity manager.
     * @var Application
     */
    protected $application;

    /**
     * Initializes the session bean with the Application instance.
     *
     * Checks on every start if the database already exists, if not
     * the database will be created immediately.
     *
     * @param Application $application The application instance
     * @return void
     */
    public function __construct(Application $application) {

        try {

            // set the application instance
            $this->application = $application;

            // check if the database already exists, if not create it
            $tool = new SchemaValidator($this->getApplication()->getEntityManager());
            if ($tool->schemaInSyncWithMetadata() === false) {
                $this->createSchema();
            }

        } catch (\Doctrine\DBAL\DBALException $e) {
            // doesn't do anything here, because SQLite is not enabled of updating the schema
        }
    }

    /**
     * The application instance providing the database connection.
     *
     * @return Application The application instance
     */
    public function getApplication() {
        return $this->application;
    }

    /**
     * Loads and returns the entity with the ID passed as parameter.
     *
     * @param integer $id The ID of the entity to load
     * @return object The entity
     */
    public function load($id) {
        $entityManager = $this->getApplication()->getEntityManager();
        return $entityManager->find('TechDivision\Example\Entities\Sample', $id);
    }

    /**
     * Persists the passed entity.
     *
     * @param Sample $entity The entity to persist
     * @return Sample The persisted entity
     */
    public function persist(Sample $entity) {
        // load the entity manager
        $entityManager = $this->getApplication()->getEntityManager();
        // check if a detached entity has been passed
        if ($entity->getSampleId()) {
            $merged = $entityManager->merge($entity);
            $entityManager->persist($merged);
        } else {
            $entityManager->persist($entity);
        }
        // flush the entity manager
        $entityManager->flush();
        // and return the entity itself
        return $entity;
    }

    /**
     * Deletes the entity with the passed ID.
     *
     * @param integer $id The ID of the entity to delete
     * @return array An array with all existing entities
     */
    public function delete($id) {
        $entityManager = $this->getApplication()->getEntityManager();
        $entityManager->remove($entityManager->merge($this->load($id)));
        $entityManager->flush();
        return $this->findAll();
    }

    /**
     * Returns an array with all existing entities.
     *
     * @return array An array with all existing entities
     */
    public function findAll() {
        $entityManager = $this->getApplication()->getEntityManager();
        $repository = $entityManager->getRepository('TechDivision\Example\Entities\Sample');
        return $repository->findAll();
    }

    /**
     * Deletes the database schema and creates it new.
     *
     * Attention: All data will be lost if this method has been invoked.
     *
     * @return void
     */
    public function createSchema() {

        // load the entity manager and the schema tool
        $entityManager = $this->getApplication()->getEntityManager();
        $tool = new SchemaTool($entityManager);

        // initialize the schema data from the entities
        $classes = array(
            $entityManager->getClassMetadata('TechDivision\Example\Entities\Assertion'),
            $entityManager->getClassMetadata('TechDivision\Example\Entities\Resource'),
            $entityManager->getClassMetadata('TechDivision\Example\Entities\Role'),
            $entityManager->getClassMetadata('TechDivision\Example\Entities\Rule'),
            $entityManager->getClassMetadata('TechDivision\Example\Entities\Sample'),
            $entityManager->getClassMetadata('TechDivision\Example\Entities\User')
        );

        // drop the schema if it already exists and create it new
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        // create a default user
        $this->createDefaultUser();
    }

    /**
     * Creates the default admin user.
     *
     * @return void
     */
    protected function createDefaultUser() {

        try {

            // load the entity manager
            $entityManager = $this->getApplication()->getEntityManager();

            // set user data and save it
            $user = new User();
            $user->setUserId(1);
            $user->setEmail('info@appserver.io');
            $user->setUsername('admin');
            $user->setUserLocale('en_US');
            $user->setPassword(md5('password'));
            $user->setEnabled(true);
            $user->setRate(1000);
            $user->setContractedHours(160);
            $user->setLdapSynced(false);
            $user->setSyncedAt(time());
            $entityManager->persist($user);

            // flush the entity manager
            $entityManager->flush();

        } catch (\Exception $e) {
            error_log($e->__toString());
        }
    }
}