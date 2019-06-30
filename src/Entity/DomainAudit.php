<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DomainAuditRepository")
 * @ORM\Table(
 *      uniqueConstraints={@UniqueConstraint(name="IDX_DOMAIN_DATE", columns={"domain_id", "date"})},
 * )
 */
class DomainAudit
{
    const CATEGORY_SEO = 'seo';
    const CATEGORY_PWA = 'pwa';
    const CATEGORY_PERFORMANCE = 'performance';
    const CATEGORY_BEST_PRACTICE = 'best_practice';
    const CATEGORY_ACCESSIBILITY = 'accessibility';
    const CATEGORY_INFORMATIVE = 'informative';

    const WEIGHTS = [
        'meta-description' => 1.0,
        'plugins' => 1.0,
        'viewport' => 1.0,
        'canonical' => 1.0,
        'is-crawlable' => 1.0,
        'tap-targets' => 1.0,
        'hreflang' => 1.0,
        'font-size' => 1.0,
        'link-text' => 1.0,
        'http-status-code' => 1.0,
        'render-blocking-resources' => 1.0,
        'uses-optimized-images' => 1.0,
        'uses-text-compression' => 1.0,
        'uses-long-cache-ttl' => 1.0,
        'interactive' => 1.0,
        'font-display' => 1.0,
        'estimated-input-latency' => 1.0,
        'uses-rel-preconnect' => 1.0,
        'unminified-css' => 1.0,
        'bootup-time' => 1.0,
        'offscreen-images' => 1.0,
        'network-server-latency' => 1.0,
        'uses-responsive-images' => 1.0,
        'speed-index' => 1.0,
        'unused-css-rules' => 1.0,
        'total-byte-weight' => 1.0,
        'mainthread-work-breakdown' => 1.0,
        'first-contentful-paint' => 1.0,
        'uses-webp-images' => 1.0,
        'dom-size' => 1.0,
        'uses-rel-preload' => 1.0,
        'unminified-javascript' => 1.0,
        'redirects' => 1.0,
        'user-timings' => 1.0,
        'first-meaningful-paint' => 1.0,
        'time-to-first-byte' => 1.0,
        'offline-start-url' => 1.0,
        'pwa-page-transitions' => 1.0,
        'load-fast-enough-for-pwa' => 1.0,
        'is-on-https' => 1.0,
        'without-javascript' => 1.0,
        'works-offline' => 1.0,
        'pwa-each-page-has-url' => 1.0,
        'content-width' => 1.0,
        'splash-screen' => 1.0,
        'pwa-cross-browser' => 1.0,
        'installable-manifest' => 1.0,
        'themed-omnibox' => 1.0,
        'service-worker' => 1.0,
        'redirects-http' => 1.0,
        'geolocation-on-start' => 1.0,
        'notification-on-start' => 1.0,
        'image-aspect-ratio' => 1.0,
        'deprecations' => 1.0,
        'doctype' => 1.0,
        'errors-in-console' => 1.0,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Domain", inversedBy="domainAudits")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $domain;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="json")
     */
    private $data = [];

    /**
     * @ORM\Column(type="json")
     */
    private $categoriesScore = [];

    /**
     * @ORM\Column(type="float", scale=2)
     */
    private $score;

    /**
     * DomainAudit constructor.
     */
    public function __construct()
    {
        try {
            $this->date = new \DateTime();
        } catch (\Exception $e) {
        }
    }

    /**
     * [
     *     self::CATEGORY_SEO => [
     *         'name' => [
     *             'source' => 'google  or gtmetrix',
     *             'group' => 'sub-category',
     *             'title' => '...............',
     *             'score' => 70, //all score must between 0 - 100
     *             'scoreType' => 'numeric or string or enum....',
     *             'value' => '',
     *             'details' => [],
     *         ],
     *     ],
     * ].
     */
    public function fixData()
    {
        foreach ($this->data as $category => &$items) {
            foreach ($items as $name => &$item) {
                foreach (['source', 'group', 'title', 'score', 'scoreType'] as $required) {
                    if (!array_key_exists($required, $item)) {
                        throw new InvalidArgumentException(sprintf('"%s" property is not exists in "%s"', $required, $name));
                    }

                    if (!array_key_exists('value', $item)) {
                        $item['value'] = '';
                    }

                    if (!array_key_exists('details', $item) or !is_array($item['details'])) {
                        $item['details'] = [];
                    }
                }
            }
        }

        ksort($this->data);

        return $this;
    }

    public function calculateScore()
    {
        $count = 0;
        $sum = 0;
        $this->categoriesScore = [];
        foreach ($this->data as $category => $items) {
            if (!array_key_exists($category, $this->categoriesScore)) {
                $this->categoriesScore[$category] = ['count' => 0, 'sum' => 0];
            }

            foreach ($items as $name => $item) {
                $weight = 1;
                if (array_key_exists($name, self::WEIGHTS)) {
                    $weight = self::WEIGHTS[$name];
                }

                if ('numeric' === $item['scoreType'] or 'binary' === $item['scoreType']) {
                    ++$count;
                    $sum += $weight * $item['score'];
                    ++$this->categoriesScore[$category]['count'];
                    $this->categoriesScore[$category]['sum'] += $weight * $item['score'];
                }
            }
        }

        if ($count > 0) {
            $this->score = $sum / $count;
        } else {
            $this->score = 0;
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCategoriesScore(): ?array
    {
        return $this->categoriesScore;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }
}
