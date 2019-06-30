<?php

namespace App\Crawler;

use App\Entity\Domain;
use App\Entity\DomainAudit;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleSpeed implements DomainAnalyzerInterface
{
    /**
     * @var Client
     */
    private $client;

    const ITEMS = [
        'meta-description' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'content'],
        'plugins' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'content', 'details' => 'details'],
        'viewport' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'mobile', 'details' => 'warnings'],
        'canonical' => ['type' => 'notApplicable', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'content'],
        'is-crawlable' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'crawl', 'details' => 'details'],
        'tap-targets' => ['type' => 'notApplicable', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'mobile'],
        'hreflang' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'content', 'details' => 'details'],
        'font-size' => ['type' => 'notApplicable', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'mobile'],
        'document-title' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'content', 'details' => 'details'],
        'robots-txt' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'crawl', 'details' => 'details', 'value' => 'displayValue'],
        'link-text' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'content', 'details' => 'details'],
        'http-status-code' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_SEO, 'group' => 'crawl'],
        'render-blocking-resources' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details', 'value' => 'displayValue'],
        'uses-optimized-images' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'uses-text-compression' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'uses-long-cache-ttl' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details', 'value' => 'displayValue'],
        'interactive' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'metrics', 'value' => 'displayValue'],
        'font-display' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details'],
        'estimated-input-latency' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'metrics', 'value' => 'displayValue'],
        'uses-rel-preconnect' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'unminified-css' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'bootup-time' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details', 'value' => 'displayValue'],
        'offscreen-images' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'network-server-latency' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details', 'value' => 'displayValue'],
        'uses-responsive-images' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details', 'value' => 'displayValue'],
        'speed-index' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'metrics', 'value' => 'displayValue'],
        'unused-css-rules' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'total-byte-weight' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details', 'value' => 'displayValue'],
        'mainthread-work-breakdown' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details', 'value' => 'displayValue'],
        'first-contentful-paint' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'metrics', 'value' => 'displayValue'],
        'uses-webp-images' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'dom-size' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details', 'value' => 'displayValue'],
        'uses-rel-preload' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'unminified-javascript' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'redirects' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details'],
        'user-timings' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'diagnostics', 'details' => 'details'],
        'first-meaningful-paint' => ['type' => 'numeric', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'metrics', 'value' => 'displayValue'],
        'time-to-first-byte' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PERFORMANCE, 'group' => 'load', 'details' => 'details', 'value' => 'displayValue'],
        'offline-start-url' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'fast-reliable', 'details' => 'warnings', 'value' => 'explanation'],
        'pwa-page-transitions' => ['type' => 'manual', 'category' => DomainAudit::CATEGORY_PWA, 'group' => ''],
        'load-fast-enough-for-pwa' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'fast-reliable'],
        'is-on-https' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'installable', 'details' => 'details', 'value' => 'displayValue'],
        'without-javascript' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'optimized'],
        'works-offline' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'fast-reliable', 'details' => 'warnings'],
        'pwa-each-page-has-url' => ['type' => 'manual', 'category' => DomainAudit::CATEGORY_PWA, 'group' => ''],
        'content-width' => ['type' => 'notApplicable', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'optimized'],
        'splash-screen' => ['type' => 'notApplicable', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'optimized', 'details' => 'details', 'value' => 'explanation'],
        'pwa-cross-browser' => ['type' => 'manual', 'category' => DomainAudit::CATEGORY_PWA, 'group' => ''],
        'installable-manifest' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'installable', 'details' => 'details', 'value' => 'explanation'],
        'themed-omnibox' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'optimized', 'details' => 'details', 'value' => 'explanation'],
        'service-worker' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'installable'],
        'redirects-http' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_PWA, 'group' => 'optimized'],
        'geolocation-on-start' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_BEST_PRACTICE, 'group' => '', 'details' => 'details'],
        'notification-on-start' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_BEST_PRACTICE, 'group' => '', 'details' => 'details'],
        'image-aspect-ratio' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_BEST_PRACTICE, 'group' => '', 'details' => 'details'],
        'deprecations' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_BEST_PRACTICE, 'group' => '', 'details' => 'details'],
        'doctype' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_BEST_PRACTICE, 'group' => ''],
        'errors-in-console' => ['type' => 'binary', 'category' => DomainAudit::CATEGORY_BEST_PRACTICE, 'group' => '', 'details' => 'details'],
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * GoogleSpeed constructor.
     *
     * @param LoggerInterface       $auditLogger
     * @param ParameterBagInterface $parameters
     */
    public function __construct(LoggerInterface $auditLogger, ParameterBagInterface $parameters)
    {
        $this->client = new Client(['headers' => ['content-type' => 'application/json', 'Accept' => 'application/json']]);
        $this->logger = $auditLogger;
        $this->parameters = $parameters;
    }

    public function analyze(Domain $domain): ?array
    {
        $url = $this->getUrl($domain);
        $this->logger->info(sprintf('Fetch google speed api for %s', $url));
        $query = sprintf(
            'category=seo&category=pwa&category=performance&category=seo&category=best-practices&key=%s&url=%s',
            getenv('GOOGLE_API_KEY'),
            $url
        );
        try {
            $response = $this->client->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', ['query' => $query]);
        } catch (RequestException $e) {
            if (isset($response) && $response instanceof ResponseInterface && 500 === $e->getResponse()->getStatusCode()) {
                $newUrl = $this->getUrl($domain, true);
                $this->logger->error(sprintf('Can not fetch google speed api for %s, try another channel.', $url));
                $this->logger->info(sprintf('Fetch google speed api for %s', $newUrl));

                $query = sprintf(
                    'category=seo&category=pwa&category=performance&category=seo&category=best-practices&key=%s&url=%s',
                    getenv('GOOGLE_API_KEY'),
                    $newUrl
                );
                $response = $this->client->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', ['query' => $query]);
            } else {
                $this->logger->warning(
                    sprintf('request responded with %s code: %s', $e->getResponse()->getStatusCode(), $e->getMessage()),
                    [$e->getTraceAsString()]
                );
            }
        }

        $json = json_decode($response->getBody()->getContents(), true);
        $result = $json['lighthouseResult']['audits'];
        $data = [];

        foreach (self::ITEMS as $item => $config) {
            if (array_key_exists($item, $result)) {
                if (!array_key_exists($config['category'], $data)) {
                    $data[$config['category']] = [];
                }
                $data[$config['category']][$item] = [
                    'source' => 'google',
                    'group' => $config['group'],
                    'title' => array_key_exists('title', $result[$item]) ? $result[$item]['title'] : '',
                    'score' => array_key_exists('score', $result[$item]) ? $result[$item]['score'] : null,
                    'scoreType' => $config['type'],
                    'value' => (array_key_exists('value', $config) && array_key_exists($config['value'], $result[$item])) ? $result[$item][$config['value']] : '',
                    'details' => (array_key_exists('details', $config) && array_key_exists($config['details'], $result[$item])) ? $result[$item][$config['details']] : [],
                ];
            }
        }

        $this->logger->info('Date is ready...');

        if (array_key_exists('final-screenshot', $result)
            && array_key_exists('details', $result['final-screenshot'])
            && array_key_exists('data', $result['final-screenshot']['details'])
        ) {
            $this->saveScreenShot($domain, $result['final-screenshot']['details']['data']);
        } else {
            $this->logger->warning('image data is not exists');
        }

        return $data;
    }

    /**
     * @param Domain $domain
     * @param $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function saveScreenShot(Domain $domain, $data)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                $this->logger->error('Can not save image: invalid image type');

                return false;
            }

            $data = base64_decode($data);

            if (false === $data) {
                $this->logger->error('Can not save image: base64_decode failed');

                return false;
            }
        } else {
            $this->logger->error('Can not save image: did not match data URI with image data');

            return false;
        }

        $path = join(DIRECTORY_SEPARATOR, [$this->parameters->get('kernel.project_dir'), 'public', 'media', 'screenshot']);
        $name = $domain->getDomain().'.'.$type;

        if (!is_dir($path)) {
            $this->logger->warning(sprintf('Directory %s is not exists, make directory...', $path));
            mkdir($path, 0755, true);
        }

        if (is_writable($path)) {
            $src = join(DIRECTORY_SEPARATOR, [$path, $name]);
            file_put_contents($src, $data, 0755);
            $domain->setScreenshot($name);
            $this->logger->info(sprintf('Saving screenshot in %s', $path));
        } else {
            $this->logger->error('Can not save image: directory is not exists or writable', [$path]);

            return false;
        }

//        $historyPath = join(
//            DIRECTORY_SEPARATOR,
//            [$this->parameters->get('kernel.project_dir'), 'public', 'media', 'screenshot', 'history', $domain->getDomain()]
//        );

//        if (!is_dir($historyPath)) {
//            $this->logger->warning(sprintf('Directory %s is not exists, make directory...', $path));
//            mkdir($historyPath, 0755, true);
//        }

//        if (is_writable($historyPath)) {
//            $historySrc = join(DIRECTORY_SEPARATOR, [$historyPath, date('Y-m').'.'.$type]);
//            if (!file_exists($historySrc)) {
//                file_put_contents($historySrc, $data, 0755);
//                $this->logger->info(sprintf('Saving history screenshot in %s', $path));
//            } else {
//                $this->logger->warning(sprintf('History screenshot exists(%s)', $path));
//            }
//        } else {
//            $this->logger->error('Can not save image: history directory is not exists or writable', [$path]);
//
//            return false;
//        }

        return true;
    }

    /**
     * @param Domain $domain
     * @param bool   $changeChannel
     *
     * @return string
     */
    private function getUrl(Domain $domain, $changeChannel = false)
    {
        if (!$changeChannel) {
            return ($domain->isSecure() ? 'https://' : 'http://').$domain->getDomain();
        } else {
            return (!$domain->isSecure() ? 'https://' : 'http://').$domain->getDomain();
        }
    }
}
