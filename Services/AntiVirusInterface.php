<?php

namespace Masilia\ClamavBundle\Services;

/**
 * Interface AntiVirusInterface
 */
interface AntiVirusInterface
{
    public function scan($filePath) : array;
}
