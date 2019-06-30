<?php

namespace App\Crawler;

use App\Entity\Domain;

interface DomainAnalyzerInterface
{
    public function analyze(Domain $domain): ?array;
}
