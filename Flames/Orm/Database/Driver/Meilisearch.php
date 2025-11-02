<?php

namespace Flames\Orm\Database\Driver;

use Exception;
use Flames\Collection\Arr;
use Flames\Http;

/**
 * @internal
 */
class Meilisearch extends DefaultEx
{
    protected const __VERSION__ = 2;

    protected $connection = null;
    protected $allIndexes = [];

    public function __construct(\Flames\Orm\Database\RawConnection\Meilisearch $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getQueryBuilder($model)
    {
        $metadata = $model::getMetadata();
        return new \Flames\Orm\Database\QueryBuilder\Meilisearch($this->connection);
    }

    public function migrate($data)
    {
        $connection = $this->getConnection();
        /** @var Http\Client $client */
        $client = $connection->getClient();

        if ($this->allIndexes === null)
            $this->allIndexes = [];

        if (in_array($data->table, $this->allIndexes) === true) {
            return true;
        }

        $request = $client->request(
            'GET',
            'indexes?limit=' . PHP_INT_MAX
        );

        // Case exists, skip
        $results = json_decode($request->getBody()->getContents())->results;
        foreach ($results as $result) {
            $this->allIndexes[] = $result->uid;
            if ($result->uid === $data->table) {
                return true;
            }
        }

        $request = $client->request(
            'POST',
            'indexes',
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode((object)[
                    'uid' => $data->table,
                    'primaryKey' => 'id'
                ])
            ]
        );

        $taskUid = (int)json_decode($request->getBody()->getContents())->taskUid;
        if ($taskUid === 0) {
            throw new Exception('Failed migrate model class ' . $data->class . ' with meilisearch API.');
        }

        $errorMessage = null;
        do {
            usleep(1000);
            $request = $client->request(
                'GET',
                ('tasks/' . $taskUid),
                [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ]
                ]
            );

            $requestData = json_decode($request->getBody()->getContents());
            $status = $requestData->status;

            if ($status === 'failed') {
                $errorMessage = (' ' . $requestData->error->message);
            }
        } while ($status === 'processing');

        if ($status !== 'succeeded') {
            throw new Exception('Failed migrate model class ' . $data->class . ' with meilisearch API.' . $errorMessage);
        }

        $this->allIndexes[] = $data->table;

        return true;
    }
}
