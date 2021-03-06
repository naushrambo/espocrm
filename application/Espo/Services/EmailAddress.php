<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;

class EmailAddress extends Record
{

    protected function findInAddressBookByEntityType($query, $limit, $entityType, &$result)
    {
        $whereClause = array(
            'OR' => array(
                array(
                    'name*' => $query . '%'
                ),
                array(
                    'emailAddress*' => $query . '%'
                )
            ),
            array(
                'emailAddress!=' => null
            )
        );

        $searchParams = array(
            'whereClause' => $whereClause,
            'orderBy' => 'name',
            'limit' => $limit
        );

        $this->getSelectManagerFactory()->create($entityType)->manageAccess($searchParams);

        $collection = $this->getEntityManager()->getRepository($entityType)->find($searchParams);

        foreach ($collection as $entity) {
            $emailAddress = $entity->get('emailAddress');

            $result[] = array(
                'emailAddress' => $emailAddress,
                'entityName' => $entity->get('name'),
                'entityType' => $entityType,
                'entityId' => $entity->id
            );

            $emailAddressData = $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($entity);
            foreach ($emailAddressData as $d) {
                if ($emailAddress != $d->emailAddress) {
                    $emailAddress = $d->emailAddress;
                    $result[] = array(
                        'emailAddress' => $emailAddress,
                        'entityName' => $entity->get('name'),
                        'entityType' => $entityType,
                        'entityId' => $entity->id
                    );
                    break;
                }
            }
        }
    }

    protected function findInInboundEmail($query, $limit, &$result)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $qu = $this->getEntityManager()->getQuery()->createSelectQuery('InboundEmail', [
            'select' => ['id', 'name', 'emailAddress'],
            'whereClause' => [
                'emailAddress*' => $query . '%'
            ],
            'orderBy' => 'name',
        ]);

        $sth = $pdo->prepare($qu);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = [
                'emailAddress' => $row['emailAddress'],
                'entityName' => $row['name'],
                'entityType' => 'InboundEmail',
                'entityId' => $row['id']
            ];
        }
    }

    public function searchInAddressBook($query, $limit)
    {
        $result = [];

        $this->findInAddressBookByEntityType($query, $limit, 'Contact', $result);
        $this->findInAddressBookByEntityType($query, $limit, 'Lead', $result);
        $this->findInAddressBookByEntityType($query, $limit, 'User', $result);
        $this->findInAddressBookByEntityType($query, $limit, 'Account', $result);
        $this->findInInboundEmail($query, $limit, $result);

        $final = array();

        foreach ($result as $r) {
            foreach ($final as $f) {
                if ($f['emailAddress'] == $r['emailAddress'] && $f['name'] == $r['name']) {
                    continue 2;
                }
            }
            $final[] = $r;
        }

        return $final;
    }

}

