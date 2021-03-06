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

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;

use \PDO;

class Activities extends \Espo\Core\Services\Base
{
    protected $dependencies = array(
        'entityManager',
        'user',
        'metadata',
        'acl',
        'selectManagerFactory'
    );

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }

    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }

    protected function getUser()
    {
        return $this->injections['user'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    protected function getSelectManagerFactory()
    {
        return $this->getInjection('selectManagerFactory');
    }

    protected function isPerson($scope)
    {
        return in_array($scope, ['Contact', 'Lead', 'User']);
    }

    protected function getUserMeetingQuery($id, $op, $notIn)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT meeting.id AS 'id', meeting.name AS 'name', meeting.date_start AS 'dateStart', meeting.date_end AS 'dateEnd', 'Meeting' AS '_scope',
                   meeting.assigned_user_id AS assignedUserId, TRIM(CONCAT(assignedUser.first_name, ' ', assignedUser.last_name)) AS assignedUserName,
                   meeting.parent_type AS 'parentType', meeting.parent_id AS 'parentId', meeting.status AS status, meeting.created_at AS createdAt
            FROM `meeting`
            LEFT JOIN `user` AS `assignedUser` ON assignedUser.id = meeting.assigned_user_id
            JOIN `meeting_user` AS `usersMiddle` ON usersMiddle.meeting_id = meeting.id AND usersMiddle.deleted = 0 AND usersMiddle.status <> 'Declined'
            WHERE meeting.deleted = 0 AND usersMiddle.user_id = ".$pdo->quote($id)."
        ";
        if (!empty($notIn)) {
            $sql .= "
                AND meeting.status {$op} ('". implode("', '", $notIn) . "')
            ";
        }
        return $sql;
    }

    protected function getUserCallQuery($id, $op, $notIn)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT call.id AS 'id', call.name AS 'name', call.date_start AS 'dateStart', call.date_end AS 'dateEnd', 'Call' AS '_scope',
                   call.assigned_user_id AS assignedUserId, TRIM(CONCAT(assignedUser.first_name, ' ', assignedUser.last_name)) AS assignedUserName,
                   call.parent_type AS 'parentType', call.parent_id AS 'parentId', call.status AS status, call.created_at AS createdAt
            FROM `call`
            LEFT JOIN `user` AS `assignedUser` ON assignedUser.id = call.assigned_user_id
            JOIN `call_user` AS `usersMiddle` ON usersMiddle.call_id = call.id AND usersMiddle.deleted = 0  AND usersMiddle.status <> 'Declined'
            WHERE call.deleted = 0 AND usersMiddle.user_id = ".$pdo->quote($id)."
        ";
        if (!empty($notIn)) {
            $sql .= "
                AND call.status {$op} ('". implode("', '", $notIn) . "')
            ";
        }
        return $sql;
    }

    protected function getUserEmailQuery($id, $op, $notIn)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT email.id AS 'id', email.name AS 'name', email.date_sent AS 'dateStart', '' AS 'dateEnd', 'Email' AS '_scope',
                   email.assigned_user_id AS assignedUserId, TRIM(CONCAT(assignedUser.first_name, ' ', assignedUser.last_name)) AS assignedUserName,
                   email.parent_type AS 'parentType', email.parent_id AS 'parentId', email.status AS status, email.created_at AS createdAt
            FROM `email`
            LEFT JOIN `user` AS `assignedUser` ON assignedUser.id = email.assigned_user_id
            JOIN `email_user` AS `usersMiddle` ON usersMiddle.email_id = email.id AND usersMiddle.deleted = 0
            WHERE email.deleted = 0 AND usersMiddle.user_id = ".$pdo->quote($id)."
        ";
        if (!empty($notIn)) {
            $sql .= "
                AND email.status {$op} ('". implode("', '", $notIn) . "')
            ";
        }
        return $sql;
    }

    protected function getMeetingQuery($scope, $id, $op = 'IN', $notIn = [])
    {
        $methodName = 'get' .$scope . 'MeetingQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $op, $notIn);
        }

        $baseSql = "
            SELECT meeting.id AS 'id', meeting.name AS 'name', meeting.date_start AS 'dateStart', meeting.date_end AS 'dateEnd', 'Meeting' AS '_scope',
                   meeting.assigned_user_id AS assignedUserId, TRIM(CONCAT(user.first_name, ' ', user.last_name)) AS assignedUserName,
                   meeting.parent_type AS 'parentType', meeting.parent_id AS 'parentId', meeting.status AS status, meeting.created_at AS createdAt
            FROM `meeting`
            LEFT JOIN `user` ON user.id = meeting.assigned_user_id
        ";

        $sql = $baseSql;
        $sql .= "
            WHERE
                meeting.deleted = 0 AND
        ";
        if ($scope == 'Account') {
            $sql .= "
                (meeting.parent_type = ".$this->getPDO()->quote($scope)." AND meeting.parent_id = ".$this->getPDO()->quote($id)."
                OR
                meeting.account_id = ".$this->getPDO()->quote($id).")
            ";
        } else {
            $sql .= "
                (meeting.parent_type = ".$this->getPDO()->quote($scope)." AND meeting.parent_id = ".$this->getPDO()->quote($id).")
            ";
        }

        if (!empty($notIn)) {
            $sql .= "
                AND meeting.status {$op} ('". implode("', '", $notIn) . "')
            ";
        }

        if ($this->isPerson($scope)) {
            $sql = $sql . "
                UNION
            " . $baseSql;

            switch ($scope) {
                case 'Contact':
                    $joinTable = 'contact_meeting';
                    $key = 'contact_id';
                    break;
                case 'Lead':
                    $joinTable = 'lead_meeting';
                    $key = 'lead_id';
                    break;
                case 'User':
                    $joinTable = 'meeting_user';
                    $key = 'user_id';
                    break;
            }
            $sql .= "
                JOIN `{$joinTable}` ON
                    meeting.id = {$joinTable}.meeting_id AND
                    {$joinTable}.deleted = 0 AND
                    {$joinTable}.{$key} = ".$this->getPDO()->quote($id)."
            ";
            $sql .= "
                WHERE
                    (
                        meeting.parent_type <> ".$this->getPDO()->quote($scope)." OR 
                        meeting.parent_id <> ".$this->getPDO()->quote($id)." OR
                        meeting.parent_type IS NULL OR
                        meeting.parent_id IS NULL
                    ) AND
                    meeting.deleted = 0
            ";
            if (!empty($notIn)) {
                $sql .= "
                    AND meeting.status {$op} ('". implode("', '", $notIn) . "')
                ";
            }

        }

        return $sql;
    }

    protected function getCallQuery($scope, $id, $op = 'IN', $notIn = [])
    {
        $methodName = 'get' .$scope . 'CallQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $op, $notIn);
        }

        $baseSql = "
            SELECT call.id AS 'id', call.name AS 'name', call.date_start AS 'dateStart', call.date_end AS 'dateEnd', 'Call' AS '_scope',
                   call.assigned_user_id AS assignedUserId, TRIM(CONCAT(user.first_name, ' ', user.last_name)) AS assignedUserName,
                   call.parent_type AS 'parentType', call.parent_id AS 'parentId', call.status AS status, call.created_at AS createdAt
            FROM `call`
            LEFT JOIN `user` ON user.id = call.assigned_user_id
        ";

        $sql = $baseSql;
        $sql .= "
            WHERE
                call.deleted = 0 AND
        ";
        if ($scope == 'Account') {
            $sql .= "
                (call.parent_type = ".$this->getPDO()->quote($scope)." AND call.parent_id = ".$this->getPDO()->quote($id)."
                OR
                call.account_id = ".$this->getPDO()->quote($id).")
            ";
        } else {
            $sql .= "
                (call.parent_type = ".$this->getPDO()->quote($scope)." AND call.parent_id = ".$this->getPDO()->quote($id).")
            ";
        }

        if (!empty($notIn)) {
            $sql .= "
                AND call.status {$op} ('". implode("', '", $notIn) . "')
            ";
        }

        if ($this->isPerson($scope)) {
            $sql = $sql . "
                UNION
            " . $baseSql;

            switch ($scope) {
                case 'Contact':
                    $joinTable = 'call_contact';
                    $key = 'contact_id';
                    break;
                case 'Lead':
                    $joinTable = 'call_lead';
                    $key = 'lead_id';
                    break;
                case 'User':
                    $joinTable = 'call_user';
                    $key = 'user_id';
                    break;
            }
            $sql .= "
                JOIN `{$joinTable}` ON
                    call.id = {$joinTable}.call_id AND
                    {$joinTable}.deleted = 0 AND
                    {$joinTable}.{$key} = ".$this->getPDO()->quote($id)."
            ";
            $sql .= "
                WHERE
                    (
                        call.parent_type <> ".$this->getPDO()->quote($scope)." OR
                        call.parent_id <> ".$this->getPDO()->quote($id)." OR
                        call.parent_type IS NULL OR
                        call.parent_id IS NULL
                    ) AND
                    call.deleted = 0
            ";
            if (!empty($notIn)) {
                $sql .= "
                    AND call.status {$op} ('". implode("', '", $notIn) . "')
                ";
            }

        }

        return $sql;
    }

    protected function getEmailQuery($scope, $id, $op = 'IN', $notIn = [])
    {
        $methodName = 'get' .$scope . 'EmailQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $op, $notIn);
        }

        $baseSql = "
            SELECT DISTINCT
                email.id AS 'id', email.name AS 'name', email.date_sent AS 'dateStart', '' AS 'dateEnd', 'Email' AS '_scope',
                email.assigned_user_id AS assignedUserId, TRIM(CONCAT(user.first_name, ' ', user.last_name)) AS assignedUserName,
                email.parent_type AS 'parentType', email.parent_id AS 'parentId', email.status AS status, email.created_at AS createdAt
            FROM `email`
            LEFT JOIN `user` ON user.id = email.assigned_user_id
        ";

        $sql = $baseSql;
        $sql .= "
            WHERE
                email.deleted = 0 AND
        ";
        if ($scope == 'Account') {
            $sql .= "
                (email.parent_type = ".$this->getPDO()->quote($scope)." AND email.parent_id = ".$this->getPDO()->quote($id)."
                OR
                email.account_id = ".$this->getPDO()->quote($id).")
            ";
        } else {
            $sql .= "
                (email.parent_type = ".$this->getPDO()->quote($scope)." AND email.parent_id = ".$this->getPDO()->quote($id).")
            ";
        }

        if (!empty($notIn)) {
            $sql .= "
                AND email.status {$op} ('". implode("', '", $notIn) . "')
            ";
        }

        if ($this->isPerson($scope) || $scope == 'Account') {
            $sql = $sql . "
                UNION
            " . $baseSql;
            $sql .= "
                LEFT JOIN entity_email_address AS entity_email_address_2 ON
                    entity_email_address_2.email_address_id = email.from_email_address_id AND
                    entity_email_address_2.entity_type = " . $this->getPDO()->quote($scope) . " AND
                    entity_email_address_2.deleted = 0

            ";
            $sql .= "
                WHERE
                    email.deleted = 0 AND
                    (
                        email.parent_type <> ".$this->getPDO()->quote($scope)." OR
                        email.parent_id <> ".$this->getPDO()->quote($id)." OR
                        email.parent_type IS NULL OR
                        email.parent_id IS NULL
                    ) AND
                    (entity_email_address_2.entity_id = ".$this->getPDO()->quote($id).")
            ";
            if (!empty($notIn)) {
                $sql .= "
                    AND email.status {$op} ('". implode("', '", $notIn) . "')
                ";
            }

            $sql = $sql . "
                UNION
            " . $baseSql;
            $sql .= "
                LEFT JOIN email_email_address ON
                    email_email_address.email_id = email.id AND
                    email_email_address.deleted = 0
                LEFT JOIN entity_email_address AS entity_email_address_1 ON
                    entity_email_address_1.email_address_id = email_email_address.email_address_id AND

                    entity_email_address_1.entity_type = " . $this->getPDO()->quote($scope) . " AND
                    entity_email_address_1.deleted = 0
            ";
            $sql .= "
                WHERE
                    email.deleted = 0 AND
                    (
                        email.parent_type <> ".$this->getPDO()->quote($scope)." OR
                        email.parent_id <> ".$this->getPDO()->quote($id)." OR
                        email.parent_type IS NULL OR
                        email.parent_id IS NULL
                    ) AND
                    (entity_email_address_1.entity_id = ".$this->getPDO()->quote($id).")
            ";
            if (!empty($notIn)) {
                $sql .= "
                    AND email.status {$op} ('". implode("', '", $notIn) . "')
                ";
            }
        }

        return $sql;
    }

    protected function getResult($parts, $scope, $id, $params)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $onlyScope = false;
        if (!empty($params['scope'])) {
            $onlyScope = $params['scope'];
        }

        if (!$onlyScope) {
            $qu = implode(" UNION ", $parts);
        } else {
            $qu = $parts[$onlyScope];
        }

        $countQu = "SELECT COUNT(*) AS 'count' FROM ({$qu}) AS c";
        $sth = $pdo->prepare($countQu);
        $sth->execute();

        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $totalCount = $row['count'];

        $qu .= "
            ORDER BY dateStart DESC, createdAt DESC
        ";

        if (!empty($params['maxSize'])) {
            $qu .= "
                LIMIT :offset, :maxSize
            ";
        }

        $sth = $pdo->prepare($qu);

        if (!empty($params['maxSize'])) {
            $offset = 0;
            if (!empty($params['offset'])) {
                $offset = $params['offset'];
            }

            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':maxSize', $params['maxSize'], PDO::PARAM_INT);
        }

        $sth->execute();

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $list = array();
        foreach ($rows as $row) {
            $list[] = $row;
        }

        return array(
            'list' => $rows,
            'total' => $totalCount
        );
    }

    protected function accessCheck($entity)
    {
        if ($entity->getEntityType() == 'User') {
            if ($this->getUser()->isAdmin()) {
                return;
            }
            $e = $this->getAcl()->get('userPermission');

            if ($this->getAcl()->get('userPermission') === 'no') {
                if ($entity->id != $this->getUser()->id) {
                    throw new Forbidden();
                }
            } else if ($this->getAcl()->get('userPermission') === 'team') {
                if ($entity->id != $this->getUser()->id) {
                    if (!$this->getUser()->has('teamsIds')) {
                        $this->getUser()->loadLinkMultipleField('teams');
                    }
                    $entity->loadLinkMultipleField('teams');
                    $teamIdList1 = $this->getUser()->get('teamsIds');
                    $teamIdList2 = $entity->get('teamsIds');

                    $inTeam = false;
                    foreach ($teamIdList1 as $id) {
                        if (in_array($id, $teamIdList2)) {
                            $inTeam = true;
                            break;
                        }
                    }
                    if (!$inTeam) {
                        throw new Forbidden();
                    }
                }
            }
        } else {
            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        }
    }

    public function getActivities($scope, $id, $params = [])
    {
        $entity = $this->getEntityManager()->getEntity($scope, $id);
        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        $parts = array();
        if ($this->getAcl()->checkScope('Meeting')) {
            $parts['Meeting'] = ($fetchAll || $params['scope'] == 'Meeting') ? $this->getMeetingQuery($scope, $id, 'NOT IN', ['Held', 'Not Held']) : [];
        }
        if ($this->getAcl()->checkScope('Call')) {
            $parts['Call'] = ($fetchAll || $params['scope'] == 'Call') ? $this->getCallQuery($scope, $id, 'NOT IN', ['Held', 'Not Held']) : [];
        }
        return $this->getResult($parts, $scope, $id, $params);
    }

    public function getHistory($scope, $id, $params)
    {
        $entity = $this->getEntityManager()->getEntity($scope, $id);

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        $parts = array();
        if ($this->getAcl()->checkScope('Meeting')) {
            $parts['Meeting'] = ($fetchAll || $params['scope'] == 'Meeting') ? $this->getMeetingQuery($scope, $id, 'IN', ['Held', 'Not Held']) : [];
        }
        if ($this->getAcl()->checkScope('Call')) {
            $parts['Call'] = ($fetchAll || $params['scope'] == 'Call') ? $this->getCallQuery($scope, $id, 'IN', ['Held', 'Not Held']) : [];
        }
        if ($this->getAcl()->checkScope('Email')) {
            $parts['Email'] = ($fetchAll || $params['scope'] == 'Email') ? $this->getEmailQuery($scope, $id, 'IN', ['Archived', 'Sent']) : [];
        }
        $result = $this->getResult($parts, $scope, $id, $params);

        foreach ($result['list'] as &$item) {
            if ($item['_scope'] == 'Email') {
                $item['dateSent'] = $item['dateStart'];
            }
        }

        return $result;
    }

    public function getEvents($userId, $from, $to)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) {
            throw new NotFound();
        }
        $this->accessCheck($user);

        $pdo = $this->getPDO();

        $sql = "
            SELECT
                'Meeting' AS scope,
                meeting.id AS id, meeting.name AS name,
                meeting.date_start AS dateStart,
                meeting.date_end AS dateEnd,
                meeting.status AS status,
                '' AS dateStartDate,
                '' AS dateEndDate
            FROM `meeting`
            JOIN meeting_user ON meeting_user.meeting_id = meeting.id AND meeting_user.deleted = 0 AND meeting_user.status <> 'Declined'
            WHERE
                meeting.deleted = 0 AND
                meeting.date_start >= ".$pdo->quote($from)." AND
                meeting.date_start < ".$pdo->quote($to)." AND
                meeting_user.user_id =".$pdo->quote($userId)."
            UNION
            SELECT
                'Call' AS scope,
                call.id AS id,
                call.name AS name,
                call.date_start AS dateStart,
                call.date_end AS dateEnd,
                call.status AS status,
                '' AS dateStartDate,
                '' AS dateEndDate
            FROM `call`
            JOIN call_user ON call_user.call_id = call.id AND call_user.deleted = 0 AND call_user.status <> 'Declined'
            WHERE
                call.deleted = 0 AND
                call.date_start >= ".$pdo->quote($from)." AND
                call.date_start < ".$pdo->quote($to)." AND
                call_user.user_id = ".$pdo->quote($userId)."
            UNION
            SELECT
                'Task' AS scope,
                task.id AS id,
                task.name AS name,
                task.date_start AS dateStart,
                task.date_end AS dateEnd,
                task.status AS status,
                task.date_start_date AS dateStartDate,
                task.date_end_date AS dateEndDate
            FROM `task`
            WHERE
                task.deleted = 0 AND
                (
                    (
                        task.date_end IS NULL AND
                        task.date_start >= ".$pdo->quote($from)." AND
                        task.date_start < ".$pdo->quote($to)."
                    ) OR (
                        task.date_end >= ".$pdo->quote($from)." AND
                        task.date_end < ".$pdo->quote($to)."
                    ) OR (
                        task.date_end_date IS NOT NULL AND
                        task.date_end_date >= ".$pdo->quote($from)." AND
                        task.date_end_date < ".$pdo->quote($to)."
                    )
                ) AND
                task.assigned_user_id = ".$pdo->quote($userId)."
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function removeReminder($id)
    {

        $pdo = $this->getPDO();
        $sql = "
            DELETE FROM `reminder`
            WHERE id = ".$pdo->quote($id)."
        ";
        if (!$this->getUser()->isAdmin()) {
            $sql .= " AND user_id = " . $pdo->quote($this->getUser()->id);
        }

        $pdo->query($sql);
        return true;

    }

    public function getPopupNotifications($userId)
    {
        $pdo = $this->getPDO();

        $dt = new \DateTime();

        $now = $dt->format('Y-m-d H:i:s');
        $nowShifted = $dt->sub(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');

        $sql = "
            SELECT id, entity_type AS 'entityType', entity_id AS 'entityId'
            FROM `reminder`
            WHERE
                `type` = 'Popup' AND
                `user_id` = ".$pdo->quote($userId)." AND
                `remind_at` <= '{$now}' AND
                `start_at` > '{$nowShifted}' AND
                `deleted` = 0
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['entityId']);
            $data = null;
            if ($entity) {
                $data = array(
                    'id' => $entity->id,
                    'entityType' => $row['entityType'],
                    'dateStart' => $entity->get('dateStart'),
                    'name' => $entity->get('name')
                );
            }
            $result[] = array(
                'id' => $row['id'],
                'data' => $data
            );

        }
        return $result;
    }

    public function getUpcomingActivities($userId, $params)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        $this->accessCheck($user);

        $entityTypeList = ['Meeting', 'Call'];

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->getAcl()->checkScope($entityType, 'read')) {
                continue;
            }


            $selectParams = array(
                'select' => ['id', 'name', 'dateStart', ['VALUE:' . $entityType, 'entityType']],
            );

            $selectManager = $this->getSelectManagerFactory()->create($entityType);


            $selectManager->applyAccess($selectParams);
            $selectManager->applyTextFilter($query, $selectParams);
            $selectManager->applyPrimaryFilter('planned', $selectParams);
            $selectManager->applyBoolFilter('onlyMy', $selectParams);
            $selectManager->applyWhere(array(
                '1' =>  array(
                    'type' => 'or',
                    'value' => array(
                        '1' => array(
                            'type' => 'today',
                            'field' => 'dateStart',
                            'dateTime' => true
                        ),
                        '2' => array(
                            'type' => 'future',
                            'field' => 'dateEnd',
                            'dateTime' => true
                        )
                    )
                )
            ), $selectParams);

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $selectParams);

            $unionPartList[] = '' . $sql . '';
        }
        if (empty($unionPartList)) {
            return array(
                'total' => 0,
                'list' => []
            );
        }

        $pdo = $this->getEntityManager()->getPDO();

        $unionSql = implode(' UNION ', $unionPartList);

        $countSql = "SELECT COUNT(*) AS 'COUNT' FROM ({$unionSql}) AS c";
        $sth = $pdo->prepare($countSql);
        $sth->execute();
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $totalCount = $row['COUNT'];

        $unionSql .= " ORDER BY dateStart ASC";
        $unionSql .= " LIMIT :offset, :maxSize";

        $sth = $pdo->prepare($unionSql);

        $sth->bindParam(':offset', intval($params['offset']), \PDO::PARAM_INT);
        $sth->bindParam(':maxSize', intval($params['maxSize']), \PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $entityDataList = [];

        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['id']);
            $entityData = $entity->toArray();
            $entityData['_scope'] = $entity->getEntityType();
            $entityDataList[] = $entityData;
        }

        return array(
            'total' => $totalCount,
            'list' => $entityDataList
        );
    }
}

