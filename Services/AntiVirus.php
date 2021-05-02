<?php

namespace Masilia\ClamavBundle\Services;

use Socket\Raw\Factory;
use Symfony\Component\HttpFoundation\File\File;
use Xenolope\Quahog\Client;

/**
 * Class AntiVirus.
 */
class AntiVirus implements AntiVirusInterface
{
    /**
     * @var string|null
     */
    private $socket = 'unix:///var/run/clamav/clamd.sock';
    /**
     * @var int
     */
    private $chmod = 0660;
    /**
     * @var string
     */
    private $rootPath;
    /**
     * @var bool
     */
    private $isStreamScan;

    /**
     * AntiVirus constructor.
     *
     * @param string|null $socket
     * @param string $rootPath
     * @param bool $isStreamScan
     */
    public function __construct(
        string $socket = null,
        string $rootPath = '',
        bool $isStreamScan = false
    ) {
        $this->socket = $socket;
        // To prevent behaviour from Symfony: Symfony replace '/opt/www/project' to '$this->targetDirs[5]',
        // we want to resolve the path, but there is no way to avoid this. In Prod environment, $this->targetDirs[5]
        // is resolved with '/', and not '/opt/www/project', so we had a double '//' to avoid Symfony to
        // do this strange behaviour...
        $this->rootPath = str_replace('//', '/', $rootPath);
        $this->isStreamScan = $isStreamScan;
    }

    /**
     * @param mixed $value
     *
     * @return array
     */
    public function scan($value): array
    {
        try {
            $socket = (new Factory())->createClient($this->socket);
            $client = new Client($socket, 1, PHP_NORMAL_READ);
            $client->startSession();

            if (!$client->ping()) {
                return ['status' => 'KO', 'reason' => 'there is an issue with socket connexion '];
            }

            $path = $value instanceof File ? $value->getPathname() : (string) $value;

            $scanResult = [];
            if (file_exists($path)) {
                @chmod($path, $this->chmod);
                if ($this->isStreamScan) {
                    $scanResult = (array) $client->scanLocalFile($this->rootPath.$path);
                } else {
                    $scanResult = (array) $client->scanFile($this->rootPath.$path);
                }
            } else {
                $scanResult['status'] = 'KO';
                $scanResult['reason'] = 'there is no file to scan or the path is wrong';
            }

            $client->endSession();

            return $scanResult;
        } catch (\Exception $e) {
            return ['status' => 'KO', 'reason' => 'file not scanned :'.$e->getMessage()];
        }
    }
}
