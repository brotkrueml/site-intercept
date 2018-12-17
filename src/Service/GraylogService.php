<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GraylogClient;
use App\Extractor\GraylogLogEntry;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Get various log messages
 */
class GraylogService
{
    /**
     * @var GraylogClient
     */
    private $client;

    /**
     * @param GraylogClient $client
     */
    public function __construct(GraylogClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get a list of graylog bamboo trigger calls and gerrit votes
     *
     * @return GraylogLogEntry[]
     */
    public function getRecentBambooTriggersAndVotes(): array
    {
        return $this->getLogs(
            'application:intercept AND level:6 AND env:prod AND (ctxt_type:triggerBamboo OR ctxt_type:voteGerrit)'
        );
    }

    /**
     * Returns an array of split / tag log entries grouped by job uuid:
     *
     * [
     *  'aUuid' => [
     *      'queueLog' => GraylogLogEntry, // Initial 'job has been queued log entry'
     *      'detailLogs' => [  // All other log rows of this job
     *          GraylogLogEntry
     *      ]
     *  ]
     * ]
     *
     * @return array
     */
    public function getRecentSplitActions(): array
    {
        $queueLogs = $this->getLogs(
            'application:intercept AND level:6 AND env:prod AND ctxt_status:queued AND (ctxt_type:patch OR ctxt_type:tag)'
        );
        $splitActions = [];
        foreach ($queueLogs as $queueLog) {
            $splitActions[$queueLog->uuid] = [
                'queueLog' => $queueLog,
                'detailLogs' => $this->getLogs(
                    'application:intercept AND level:6 AND env:prod'
                    . ' AND !(ctxt_status:queued)'
                    . ' AND (ctxt_type:patch OR ctxt_type:tag)'
                    . ' AND ctxt_job_uuid:' . $queueLog->uuid,
                    500
                ),
            ];
        }
        return $splitActions;
    }

    /**
     * @param string $query
     * @param int $limit
     * @return GraylogLogEntry[]
     */
    private function getLogs(string $query, int $limit = 40): array
    {
        $query = urlencode($query);
        try {
            $response = $this->client->get(
                'search/universal/relative'
                . '?query=' . $query
                . '&range=2592000' // 30 days max
                . '&limit=' . $limit
                . '&sort=' . urlencode('timestamp:desc')
                . '&pretty=true',
                [
                    'auth' => [getenv('GRAYLOG_TOKEN'), 'token'],
                ]
            );
            $content = json_decode((string)$response->getBody(), true);
            $messages = [];
            if (isset($content['messages']) && is_array($content['messages'])) {
                foreach ($content['messages'] as $message) {
                    $messages[] = new GraylogLogEntry($message['message']);
                }
            }
            return $messages;
        } catch (ClientException $e) {
            // Silent fail if graylog is broken
            return [];
        } catch (ConnectException $e) {
            // Silent fail if graylog is down
            return [];
        }
    }
}
